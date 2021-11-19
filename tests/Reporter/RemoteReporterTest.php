<?php

declare(strict_types=1);

namespace JagerPhp\Tests\Reporter;

use Jaeger\SpanContext;
use PHPUnit\Framework\TestCase;

class RemoteReporterTest extends TestCase
{
    public function getSpanContext(): SpanContext
    {
        return new SpanContext(1562237095801441413, 0, 1, null, 1);
    }

    public function testClose(): void
    {
        $this->assertTrue(true);
    }
}