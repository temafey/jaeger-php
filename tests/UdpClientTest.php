<?php

declare(strict_types=1);

namespace JagerPhp\Tests;

use Jaeger\Thrift\AgentClient;
use Jaeger\UdpClient;
use PHPUnit\Framework\TestCase;

class UdpClientTest extends TestCase
{
    public ?UdpClient $udpClient;

    public ?AgentClient $agentClient;

    public function setUp(): void
    {
        $this->agentClient = $this->createMock(AgentClient::class);
        $this->udpClient = new UdpClient('localhost:6831', $this->agentClient);
    }

    public function testIsOpen(): void
    {
        self::assertTrue($this->udpClient->isOpen());
    }

    public function testEmitBatch(): void
    {

        $this->agentClient->expects($this->once())->method('buildThrift')
            ->willReturn(['len' => 3, 'thriftStr' => 123]);
        $batch = [
            'thriftProcess' => ''
            , 'thriftSpans' => ''
        ];

        self::assertTrue($this->udpClient->emitBatch($batch));
    }

    public function testEmitBatchFalse(): void
    {
        $batch = [
            'thriftProcess' => ''
            , 'thriftSpans' => ''
        ];

        $this->agentClient->expects($this->any())->method('buildThrift')
            ->willReturn(['thriftStr' => 123]);

        self::assertFalse($this->udpClient->emitBatch($batch));

        $this->udpClient->close();
        $this->agentClient->expects($this->any())->method('buildThrift')
            ->willReturn(['len' => 3, 'thriftStr' => 123]);


        self::assertFalse($this->udpClient->emitBatch($batch));
    }

    public function testClose(): void
    {
        $this->udpClient->close();
        self::assertFalse($this->udpClient->isOpen());
    }
}