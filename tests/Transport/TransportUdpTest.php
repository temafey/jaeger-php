<?php

declare(strict_types=1);

namespace JagerPhp\Tests\Transport;

use Jaeger\Transport\TransportUdp;
use PHPUnit\Framework\TestCase;

class TransportUdpTest extends TestCase
{
    public ?TransportUdp $tran;

    public function setUp(): void
    {
        $this->tran = new TransportUdp('localhost:6831');
    }

    public function testResetBuffer(): void
    {
        $this->tran->resetBuffer();
        self::assertCount(0, $this->tran->getBatchs());
    }
}