<?php

declare(strict_types=1);

namespace JagerPhp\Tests\Propagator;

use Jaeger\Constants;
use Jaeger\Propagator\JaegerPropagator;
use Jaeger\SpanContext;
use PHPUnit\Framework\TestCase;
use const Jaeger\Constants\Trace_Baggage_Header_Prefix;
use const Jaeger\Constants\Tracer_State_Header_Name;
use const OpenTracing\Formats\TEXT_MAP;

class JaegerPropagatorTest extends TestCase
{
    public function getSpanContext(): SpanContext
    {
        return new SpanContext(1562237095801441413, 0, 1, null, 1);
    }

    public function testInject(): void
    {
        $context = $this->getSpanContext();
        $context->traceIdLow = 1562237095801441413;
        $jaeger = new JaegerPropagator();
        $carrier = [];

        $jaeger->inject($context, TEXT_MAP, $carrier);
        self::assertTrue(
            $carrier[strtoupper(Tracer_State_Header_Name)] == '15ae2e5c8e2ecc85:15ae2e5c8e2ecc85:0:1'
        );
    }

    public function testInject128Bit(): void
    {
        $context = $this->getSpanContext();
        $context->traceIdLow = 1562289663898779811;
        $context->traceIdHigh = 1562289663898881723;

        $jaeger = new JaegerPropagator();
        $carrier = [];

        $jaeger->inject($context, TEXT_MAP, $carrier);
        self::assertTrue(
            $carrier[strtoupper(Tracer_State_Header_Name)]
            == '15ae5e2c04f50ebb15ae5e2c04f380a3:15ae2e5c8e2ecc85:0:1'
        );
    }

    public function testExtract(): void
    {
        $jaeger = new JaegerPropagator();
        $carrier = [];
        $carrier[strtoupper(Tracer_State_Header_Name)] = '15ae2e5c8e2ecc85:15ae2e5c8e2ecc85:0:1';

        $context = $jaeger->extract(TEXT_MAP, $carrier);
        self::assertTrue($context->traceIdLow == 1562237095801441413);
        self::assertTrue($context->parentId == 0);
        self::assertTrue($context->spanId == 1562237095801441413);
        self::assertTrue($context->flags == 1);
    }

    public function testExtractDebugId(): void
    {
        $jaeger = new JaegerPropagator();
        $carrier[Trace_Baggage_Header_Prefix . 'baggage'] = 2;

        $context = $jaeger->extract(TEXT_MAP, $carrier);
        self::assertTrue($context->debugId == 0);

        $carrier[Constants\Jaeger_Debug_Header] = 1;
        $context = $jaeger->extract(TEXT_MAP, $carrier);
        self::assertTrue($context->debugId == 1);
    }

    public function testExtractUberctx(): void
    {
        $jaeger = new JaegerPropagator();

        $carrier[Trace_Baggage_Header_Prefix] = '2.0.0';
        $carrier[Constants\Jaeger_Debug_Header] = true;
        $context = $jaeger->extract(TEXT_MAP, $carrier);
        self::assertTrue($context->baggage == null);

        $carrier = [];

        $carrier[Trace_Baggage_Header_Prefix . 'version'] = '2.0.0';
        $context = $jaeger->extract(TEXT_MAP, $carrier);
        self::assertTrue($context->getBaggageItem('version') == '2.0.0');
    }

    public function testExtractBaggageHeader(): void
    {
        $jaeger = new JaegerPropagator();
        $carrier = [];

        $carrier[Constants\Jaeger_Baggage_Header] = 'version=2.0.0,os=1';
        $context = $jaeger->extract(TEXT_MAP, $carrier);
        self::assertTrue($context->getBaggageItem('version') == '2.0.0');
        self::assertTrue($context->getBaggageItem('os') == '1');
    }

    public function testExtractBadBaggageHeader(): void
    {
        $jaeger = new JaegerPropagator();

        $carrier = [];

        $carrier[Constants\Jaeger_Baggage_Header] = 'version';
        $carrier[Constants\Jaeger_Debug_Header] = true;
        $context = $jaeger->extract(TEXT_MAP, $carrier);
        self::assertTrue($context->baggage == null);
    }

    public function testExtract128Bit(): void
    {
        $jaeger = new JaegerPropagator();
        $carrier = [];
        $carrier[strtoupper(Tracer_State_Header_Name)]
            = '15ae5e2c04f50ebb15ae5e2c04f380a3:15ae2e5c8e2ecc85:0:1';

        $context = $jaeger->extract(TEXT_MAP, $carrier);
        self::assertTrue($context->traceIdLow == 1562289663898779811);
        self::assertTrue($context->traceIdHigh == 1562289663898881723);
        self::assertTrue($context->parentId == 0);
        self::assertTrue($context->spanId == 1562237095801441413);
        self::assertTrue($context->flags == 1);
    }

//    public function testExtractPsr7(): void
//    {
//        $jaeger = new JaegerPropagator();
//        $carrier = [];
//        $carrier[] = [strtoupper(Tracer_State_Header_Name) => '15ae2e5c8e2ecc85:15ae2e5c8e2ecc85:0:1'];
//
//        $context = $jaeger->extract(TEXT_MAP, $carrier);
//        self::assertTrue($context->traceIdLow == 1562237095801441413);
//        self::assertTrue($context->parentId == 0);
//        self::assertTrue($context->spanId == 1562237095801441413);
//        self::assertTrue($context->flags == 1);
//    }

    public function testExtractReturnsNull(): void
    {
        $jaeger = new JaegerPropagator();
        $carrier = [];

        $context = $jaeger->extract(TEXT_MAP, $carrier);
        self::assertNull($context);
    }
}
