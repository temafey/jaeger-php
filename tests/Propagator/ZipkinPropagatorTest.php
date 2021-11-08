<?php

declare(strict_types=1);

namespace JagerPhp\Tests\Propagator;

use Jaeger\Propagator\ZipkinPropagator;
use Jaeger\SpanContext;
use PHPUnit\Framework\TestCase;
use const Jaeger\Constants\X_B3_PARENT_SPANID;
use const Jaeger\Constants\X_B3_SAMPLED;
use const Jaeger\Constants\X_B3_SPANID;
use const Jaeger\Constants\X_B3_TRACEID;
use const OpenTracing\Formats\TEXT_MAP;

class ZipkinPropagatorTest extends TestCase
{
    public function getSpanContext(): SpanContext
    {
        return new SpanContext(1562237095801441413, 0, 1, null, 1);
    }

    public function testInject(): void
    {
        $context = $this->getSpanContext();
        $context->traceIdLow = 1562237095801441413;
        $zipkin = new ZipkinPropagator();
        $carrier = [];

        $zipkin->inject($context, TEXT_MAP, $carrier);

        self::assertTrue($carrier[X_B3_TRACEID] == '15ae2e5c8e2ecc85');
        self::assertTrue($carrier[X_B3_PARENT_SPANID] == 0);
        self::assertTrue($carrier[X_B3_SPANID] == '15ae2e5c8e2ecc85');
        self::assertTrue($carrier[X_B3_SAMPLED] == 1);
    }

    public function testInject128Bit(): void
    {
        $context = $this->getSpanContext();
        $context->traceIdLow = 1562289663898779811;
        $context->traceIdHigh = 1562289663898881723;

        $zipkin = new ZipkinPropagator();
        $carrier = [];

        $zipkin->inject($context, TEXT_MAP, $carrier);

        self::assertTrue($carrier[X_B3_TRACEID] == '15ae5e2c04f50ebb15ae5e2c04f380a3');
        self::assertTrue($carrier[X_B3_PARENT_SPANID] == 0);
        self::assertTrue($carrier[X_B3_SPANID] == '15ae2e5c8e2ecc85');
        self::assertTrue($carrier[X_B3_SAMPLED] == 1);
    }

    public function testExtract(): void
    {
        $zipkin = new ZipkinPropagator();
        $carrier = [];
        $carrier[X_B3_TRACEID] = '15ae2e5c8e2ecc85';
        $carrier[X_B3_PARENT_SPANID] = 1;
        $carrier[X_B3_SPANID] = '15ae2e5c8e2ecc85';
        $carrier[X_B3_SAMPLED] = 1;

        $context = $zipkin->extract(TEXT_MAP, $carrier);
        self::assertTrue($context->traceIdLow == '1562237095801441413');
        self::assertTrue($context->parentId == 1);
        self::assertTrue($context->spanId == '1562237095801441413');
        self::assertTrue($context->flags == 1);
    }

    public function testExtract128Bit(): void
    {
        $zipkin = new ZipkinPropagator();
        $carrier = [];
        $carrier[X_B3_TRACEID] = '15ae5e2c04f50ebb15ae5e2c04f380a3';
        $carrier[X_B3_PARENT_SPANID] = 0;
        $carrier[X_B3_SPANID] = '15ae5e2c04f380a3';
        $carrier[X_B3_SAMPLED] = 1;

        $context = $zipkin->extract(TEXT_MAP, $carrier);
        self::assertTrue($context->traceIdLow == 1562289663898779811);
        self::assertTrue($context->traceIdHigh == 1562289663898881723);
        self::assertTrue($context->parentId == 0);
        self::assertTrue($context->spanId == 1562289663898779811);
        self::assertTrue($context->flags == 1);
    }

    public function testExtractReturnsNull(): void
    {
        $jaeger = new ZipkinPropagator();
        $carrier = [];

        $context = $jaeger->extract(TEXT_MAP, $carrier);
        self::assertNull($context);
    }
}
