<?php

declare(strict_types=1);

namespace JagerPhp\Tests;

use Jaeger\SpanContext;
use PHPUnit\Framework\TestCase;

class SpanContextTest extends TestCase
{
    public function getSpanContext(): SpanContext
    {
        return new SpanContext(1, 1, 1, null, 1);
    }

    public function testNew(): void
    {
        $spanContext = $this->getSpanContext();
        self::assertInstanceOf(SpanContext::class, $spanContext);
    }

    public function testWithBaggageItem(): void
    {
        $spanContext = $this->getSpanContext();
        $res = $spanContext->withBaggageItem('version', '2.0.0');
        self::assertEquals($res->getBaggageItem('version'), '2.0.0');
    }

    public function testGetBaggageItem(): void
    {
        $spanContext = $this->getSpanContext();
        $res = $spanContext->withBaggageItem('version', '2.0.0');
        self::assertEquals(
            $spanContext->getBaggageItem('version'),
            $res->getBaggageItem('version')
        );

        $service = $spanContext->getBaggageItem('service');
        self::assertNull($service);
    }

    public function testBuildString(): void
    {
        $spanContext = $this->getSpanContext();
        $spanContext->traceIdLow = 1;
        self::assertTrue($spanContext->buildString() == '1:1:1:1');

        $spanContext->traceIdHigh = 1;
        self::assertTrue($spanContext->buildString() == '10000000000000001:1:1:1');
    }

    public function testSpanIdToString(): void
    {
        $spanContext = $this->getSpanContext();
        self::assertTrue($spanContext->spanIdToString() == '1');

        $spanContext->spanId = "111111";
        self::assertTrue($spanContext->spanIdToString() == '1b207');
    }

    public function testTraceIdLowToString()
    {
        $spanContext = $this->getSpanContext();
        $spanContext->traceIdLow = "111111";
        self::assertTrue($spanContext->traceIdLowToString() == '1b207');

        $spanContext->traceIdHigh = "111111";
        self::assertTrue($spanContext->traceIdLowToString() == '1b207000000000001b207');
    }

    public function testTraceIdToString(): void
    {
        $spanContext = $this->getSpanContext();
        $spanContext->traceIdToString('1b207000000000001b207');
        self::assertTrue($spanContext->traceIdLow == '111111');
        self::assertTrue($spanContext->traceIdHigh == '1954685383581106176');

        $spanContext->traceIdLow = null;
        $spanContext->traceIdHigh = null;
        $spanContext->traceIdToString('1b207');
        self::assertTrue($spanContext->traceIdLow == '111111');
        self::assertTrue(!$spanContext->traceIdHigh);
    }
}