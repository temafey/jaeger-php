<?php

declare(strict_types=1);

namespace Jaeger;

use Exception;
use Jaeger\Thrift\AgentClient;

/**
 * Send Thrift to jaeger-agent
 */
class UdpClient
{
    private string $host;

    private int $post;

    private $socket;

    private AgentClient $agentClient;

    public function __construct($hostPost, AgentClient $agentClient)
    {
        list($host, $post) = explode(":", $hostPost);
        $this->host = $host;
        $this->post = (int)$post;
        $this->agentClient = $agentClient;
        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    }

    public function isOpen(): bool
    {
        return $this->socket !== null;
    }

    /**
     * Send Thrift
     *
     * @throws Exception
     */
    public function emitBatch(array $batch): bool
    {
        $buildThrift = $this->agentClient->buildThrift($batch);
        if (isset($buildThrift['len']) && $buildThrift['len'] && $this->isOpen()) {
            $len = $buildThrift['len'];
            $emitThrift = (string)$buildThrift['thriftStr'];
            $res = socket_sendto($this->socket, $emitThrift, $len, 0, $this->host, $this->post);
            if (false === $res) {
                throw new Exception(sprintf("Batch emit failed [THRIFT: %s]", $emitThrift));
            }

            return true;
        } else {
            return false;
        }
    }

    public function close()
    {
        socket_close($this->socket);
        $this->socket = null;
    }
}