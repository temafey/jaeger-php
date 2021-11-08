<?php

declare(strict_types=1);

namespace JagerPhp\Tests;

use Jaeger\Span;
use Jaeger\SpanContext;
use OpenTracing\NoopSpanContext;
use PHPUnit\Framework\TestCase;

class SpanTest extends TestCase
{
    public function testOverwriteOperationName(): void
    {
        $span = new Span('test', new NoopSpanContext(), []);
        $span->overwriteOperationName('test2');
        self::assertTrue($span->getOperationName() == 'test2');
    }

    public function testAddTags(): void
    {
        $span = new Span('test', new NoopSpanContext(), []);
        $span->setTag('test', 'test');
        self::assertTrue((isset($span->tags['test']) && $span->tags['test'] == 'test'));
    }

    public function testFinish(): void
    {
        $span = new Span('test', new NoopSpanContext(), []);
        $span->setTag('test', 'test');
        $span->finish();
        self::assertTrue(!empty($span->finishTime) && !empty($span->duration));
    }

    public function testGetContext(): void
    {
        $span = new Span('test', new NoopSpanContext(), []);
        $spanContext = $span->getContext();
        self::assertInstanceOf(NoopSpanContext::class, $spanContext);
    }

    public function testLog(): void
    {
        $span = new Span('test', new NoopSpanContext(), []);
        $logs = [
            'msg' => 'is test',
            'msg2' => 'is msg 2'
        ];
        $span->log($logs);
        self::assertTrue(count($span->logs) == 1);
    }

    public function testGetBaggageItem(): void
    {
        $span = new Span('test', new SpanContext(0, 0, 0), []);
        $span->addBaggageItem('version', '2.0.0');

        $version = $span->getBaggageItem('version');
        self::assertEquals('2.0.0', $version);

        $service = $span->getBaggageItem('service');
        self::assertNull($service);
    }
}