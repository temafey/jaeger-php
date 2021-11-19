<?php

declare(strict_types=1);

namespace JagerPhp\Tests;

use Jaeger\Jaeger;
use Jaeger\Propagator\JaegerPropagator;
use Jaeger\Reporter\RemoteReporter;
use Jaeger\Sampler\ConstSampler;
use Jaeger\ScopeManager;
use Jaeger\Span;
use Jaeger\SpanContext;
use Jaeger\Transport\TransportUdp;
use OpenTracing\Reference;
use PHPUnit\Framework\TestCase;
use const Jaeger\Constants\Tracer_State_Header_Name;
use const OpenTracing\Formats\HTTP_HEADERS;
use const OpenTracing\Formats\TEXT_MAP;

class JaegerTest extends TestCase
{
    public function getJaeger(): Jaeger
    {
        $tranSport = new TransportUdp();
        $reporter = new RemoteReporter($tranSport);
        $sampler = new ConstSampler();
        $scopeManager = new ScopeManager();

        return new Jaeger('jaeger', $reporter, $sampler, $scopeManager);
    }

    public function testNew(): void
    {
        $Jaeger = $this->getJaeger();
        self::assertInstanceOf(Jaeger::class, $Jaeger);
    }

    public function testGetEnvTags(): void
    {

        $_SERVER['JAEGER_TAGS'] = 'a=b,c=d';
        $Jaeger = $this->getJaeger();
        $tags = $Jaeger->getEnvTags();

        self::assertTrue(count($tags) > 0);
    }

    public function testSetTags(): void
    {
        $Jaeger = $this->getJaeger();

        $Jaeger->setTags(['version' => '2.0.0']);
        self::assertTrue($Jaeger->tags['version'] == '2.0.0');
    }

    public function testInject(): void
    {
        $Jaeger = $this->getJaeger();
        $Jaeger->setPropagator(new JaegerPropagator());

        $context = new SpanContext(1, 1, 1, null, 1);

        $Jaeger->inject($context, TEXT_MAP, $_SERVER);
        self::assertTrue('0:1:1:1' == $_SERVER[strtoupper(Tracer_State_Header_Name)]);
    }

    public function testInjectUnSupportFormat(): void
    {
        $Jaeger = $this->getJaeger();
        $Jaeger->setPropagator(new JaegerPropagator());

        $context = new SpanContext(1, 1, 1, null, 1);
        $this->expectExceptionMessage('The format "http_headers" is not supported.');

        $Jaeger->inject($context, HTTP_HEADERS, $_SERVER);
    }

    public function testExtract(): void
    {
        $Jaeger = $this->getJaeger();
        $Jaeger->setPropagator(new JaegerPropagator());

        $carrier[strtoupper(Tracer_State_Header_Name)] = '1:1:1:1';
        $spanContext = $Jaeger->extract(TEXT_MAP, $carrier);
        self::assertTrue($spanContext->parentId == 1);
        self::assertTrue($spanContext->traceIdLow == 1);
        self::assertTrue($spanContext->flags == 1);
        self::assertTrue($spanContext->spanId == 1);
    }

    public function testExtractUnSupportFormat(): void
    {
        $Jaeger = $this->getJaeger();
        $Jaeger->setPropagator(new JaegerPropagator());

        $_SERVER[strtoupper(Tracer_State_Header_Name)] = '1:1:1:1';
        $this->expectExceptionMessage('The format "http_headers" is not supported.');

        $Jaeger->extract(HTTP_HEADERS, $_SERVER);
    }

    public function testStartSpan(): void
    {
        $Jaeger = $this->getJaeger();
        $span = $Jaeger->startSpan('test');
        self::assertNotEmpty($span->startTime);
        self::assertNotEmpty($Jaeger->getSpans());
    }

    public function testStartSpanWithFollowsFromTypeRef(): void
    {
        $jaeger = $this->getJaeger();
        $rootSpan = $jaeger->startSpan('root-a');
        $childSpan = $jaeger->startSpan('span-a', [
            'references' => new Reference(Reference::FOLLOWS_FROM, $rootSpan->getContext()),
        ]);

        self::assertSame($childSpan->getContext()->traceIdLow, $rootSpan->getContext()->traceIdLow);
        self::assertSame(current($childSpan->references)->getSpanContext(), $rootSpan->getContext());

        $otherRootSpan = $jaeger->startSpan('root-a');
        $childSpan = $jaeger->startSpan('span-b', [
            'references' => [
                new Reference(Reference::FOLLOWS_FROM, $rootSpan->getContext()),
                new Reference(Reference::FOLLOWS_FROM, $otherRootSpan->getContext()),
            ],
        ]);

        self::assertSame($childSpan->getContext()->traceIdLow, $otherRootSpan->getContext()->traceIdLow);
    }

    public function testStartSpanWithChildOfTypeRef(): void
    {
        $jaeger = $this->getJaeger();
        $rootSpan = $jaeger->startSpan('root-a');
        $otherRootSpan = $jaeger->startSpan('root-b');
        $childSpan = $jaeger->startSpan('span-a', [
            'references' => [
                new Reference(Reference::CHILD_OF, $rootSpan->getContext()),
                new Reference(Reference::CHILD_OF, $otherRootSpan->getContext()),
            ],
        ]);

        self::assertSame($childSpan->getContext()->traceIdLow, $rootSpan->getContext()->traceIdLow);
    }

    public function testStartSpanWithCustomStartTime(): void
    {
        $jaeger = $this->getJaeger();
        $span = $jaeger->startSpan('test', ['start_time' => 1499355363.123456]);

        self::assertSame(1499355363123456, $span->startTime);
    }

    public function testStartSpanWithAllRefType(): void
    {
        $jaeger = $this->getJaeger();
        $rootSpan = $jaeger->startSpan('root-a');
        $otherRootSpan = $jaeger->startSpan('root-b');
        $childSpan = $jaeger->startSpan('span-a', [
            'references' => [
                new Reference(Reference::FOLLOWS_FROM, $rootSpan->getContext()),
                new Reference(Reference::CHILD_OF, $otherRootSpan->getContext()),
            ],
        ]);

        self::assertSame($childSpan->getContext()->traceIdLow, $otherRootSpan->getContext()->traceIdLow);
    }

    public function testReportSpan(): void
    {
        $Jaeger = $this->getJaeger();
        $Jaeger->startSpan('test');
        $Jaeger->reportSpan();
        self::assertEmpty($Jaeger->getSpans());
    }

    public function testStartActiveSpan(): void
    {
        $Jaeger = $this->getJaeger();
        $Jaeger->startActiveSpan('test');

        self::assertNotEmpty($Jaeger->getSpans());
    }

    public function testGetActiveSpan(): void
    {
        $Jaeger = $this->getJaeger();
        $Jaeger->startActiveSpan('test');

        $span = $Jaeger->getActiveSpan();

        self::assertInstanceOf(Span::class, $span);
    }

    public function testFlush(): void
    {
        $Jaeger = $this->getJaeger();
        $Jaeger->startSpan('test');
        $Jaeger->flush();
        self::assertEmpty($Jaeger->getSpans());
    }

    public function testNestedSpanBaggage(): void
    {
        $tracer = $this->getJaeger();

        $parent = $tracer->startSpan('parent');
        $parent->addBaggageItem('key', 'value');

        $child = $tracer->startSpan('child', [Reference::CHILD_OF => $parent]);

        self::assertEquals($parent->getBaggageItem('key'), $child->getBaggageItem('key'));
    }
}
