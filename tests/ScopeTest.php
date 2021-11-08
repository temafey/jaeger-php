<?php

declare(strict_types=1);

namespace JagerPhp\Tests;

use Jaeger\ScopeManager;
use Jaeger\Span;
use OpenTracing\NoopSpanContext;
use PHPUnit\Framework\TestCase;

class ScopeTest extends TestCase
{
    public function testClose(): void
    {
        $span1 = new Span('test', new NoopSpanContext(), []);

        $scopeManager = new ScopeManager();
        $scope = $scopeManager->activate($span1, true);
        $scope->close();

        self::assertTrue($scopeManager->getActive() === null);
    }

    public function testGetSpan(): void
    {
        $span1 = new Span('test', new NoopSpanContext(), []);

        $scopeManager = new ScopeManager();
        $scope = $scopeManager->activate($span1, true);

        self::assertTrue($scope->getSpan() !== null);
    }
}