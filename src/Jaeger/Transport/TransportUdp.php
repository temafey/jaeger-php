<?php

declare(strict_types=1);

namespace Jaeger\Transport;

use Jaeger\Constants;
use Jaeger\Jaeger;
use Jaeger\Thrift\AgentClient;
use Jaeger\Thrift\JaegerThriftSpan;
use Jaeger\Thrift\Process;
use Jaeger\Thrift\Span;
use Jaeger\Thrift\TStruct;
use Jaeger\Transport\Transport as TransportInterface;
use Jaeger\UdpClient;
use Thrift\Protocol\TCompactProtocol;
use Thrift\Transport\TMemoryBuffer;
use const Jaeger\Constants\EMIT_BATCH_OVER_HEAD;
use const Jaeger\Constants\UDP_PACKET_MAX_LENGTH;

class TransportUdp implements TransportInterface
{
    private $tran;

    public static $hostPort = '';

    // sizeof(Span) * numSpans + processByteSize + emitBatchOverhead <= maxPacketSize
    public static $maxSpanBytes = 0;

    public static $batchs = [];

    public $agentServerHostPort = '0.0.0.0:5775';

    public $thriftProtocol = null;

    public $procesSize = 0;

    public $bufferSize = 0;

    const MAC_UDP_MAX_SIZE = 9216;

    public function __construct($hostPort = '', $maxPacketSize = '')
    {
        if ($hostPort == "") {
            $hostPort = $this->agentServerHostPort;
        }
        self::$hostPort = $hostPort;

        if ($maxPacketSize == 0) {
            $maxPacketSize = stristr(PHP_OS, 'DAR') ? self::MAC_UDP_MAX_SIZE : UDP_PACKET_MAX_LENGTH;
        }

        self::$maxSpanBytes = (int)$maxPacketSize - EMIT_BATCH_OVER_HEAD;

        $this->tran = new TMemoryBuffer();
        $this->thriftProtocol = new TCompactProtocol($this->tran);
    }


    public function buildAndCalcSizeOfProcessThrift(Jaeger $jaeger)
    {
        $jaeger->processThrift = (new JaegerThriftSpan())->buildJaegerProcessThrift($jaeger);
        $jaeger->process = (new Process($jaeger->processThrift));
        $this->procesSize = $this->getAndCalcSizeOfSerializedThrift($jaeger->process, $jaeger->processThrift);
        $this->bufferSize += $this->procesSize;
    }


    /**
     * @return bool
     */
    public function append(Jaeger $jaeger)
    {
        if ($jaeger->process == null) {
            $this->buildAndCalcSizeOfProcessThrift($jaeger);
        }

        $thriftSpansBuffer = [];  // Uncommitted span used to temporarily store shards

        foreach ($jaeger->spans as $span) {
            $spanThrift = (new JaegerThriftSpan())->buildJaegerSpanThrift($span);
            $agentSpan = Span::getInstance();
            $agentSpan->setThriftSpan($spanThrift);
            $spanSize = $this->getAndCalcSizeOfSerializedThrift($agentSpan, $spanThrift);
            if ($spanSize > self::$maxSpanBytes) {
                //throw new \Exception("Span is too large");
                continue;
            }
            if ($this->bufferSize + $spanSize >= self::$maxSpanBytes) {
                self::$batchs[] = [
                    'thriftProcess' => $jaeger->processThrift,
                    'thriftSpans' => $thriftSpansBuffer,
                ];
                $this->flush();
                $thriftSpansBuffer = [];  // Empty the temp buffer
            }

            $thriftSpansBuffer[] = $spanThrift;
            $this->bufferSize += $spanSize;
        }
        if ($thriftSpansBuffer) {
            self::$batchs[] = [
                'thriftProcess' => $jaeger->processThrift,
                'thriftSpans' => $thriftSpansBuffer,
            ];
            $this->flush();
        }

        return true;
    }

    public function resetBuffer()
    {
        $this->bufferSize = $this->procesSize;
        self::$batchs = [];
    }

    private function getAndCalcSizeOfSerializedThrift(TStruct $ts, &$serializedThrift)
    {
        $ts->write($this->thriftProtocol);
        $serThriftStrlen = $this->tran->available();
        $serializedThrift['wrote'] = $this->tran->read(UDP_PACKET_MAX_LENGTH);

        return $serThriftStrlen;
    }

    /**
     * @return int
     */
    public function flush()
    {
        $batchNum = count(self::$batchs);
        if ($batchNum <= 0) {
            return 0;
        }

        $spanNum = 0;
        $udp = new UdpClient(self::$hostPort, new AgentClient());

        foreach (self::$batchs as $batch) {
            $spanNum += count($batch['thriftSpans']);
            $udp->emitBatch($batch);
        }

        $udp->close();
        $this->resetBuffer();

        return $spanNum;
    }

    public function getBatchs(): array
    {
        return self::$batchs;
    }
}