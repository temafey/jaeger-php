<?php

declare(strict_types=1);

namespace JagerPhp\Tests;

use Jaeger\ScopeManager;
use Jaeger\Span;
use OpenTracing\NoopSpanContext;
use PHPUnit\Framework\TestCase;

class ScopeMangerTest extends TestCase
{
    public function testActivate(): void
    {
        $span1 = new Span('test', new NoopSpanContext(), []);

        $scopeManager = new ScopeManager();
        $scope = $scopeManager->activate($span1, true);
        $span2 = $scope->getSpan();

        self::assertTrue($span1 === $span2);
    }

    public function testGetActive(): void
    {
        $span = new Span('test', new NoopSpanContext(), []);

        $scopeManager = new ScopeManager();
        $scope1 = $scopeManager->activate($span, true);

        $scope2 = $scopeManager->getActive();
        self::assertTrue($scope1 === $scope2);
    }

    public function testDelActive(): void
    {
        $span = new Span('test', new NoopSpanContext(), []);

        $scopeManager = new ScopeManager();
        $scope = $scopeManager->activate($span, true);

        $res = $scopeManager->delActive($scope);
        self::assertTrue($res == true);

        $getRes = $scopeManager->getActive();
        self::assertTrue($getRes === null);
    }

    public function testDelActiveNestedScopes(): void
    {
        $scopeManager = new ScopeManager();
        $span1 = new Span('Z', new NoopSpanContext(), []);
        $scope1 = $scopeManager->activate($span1, true);
        $span2 = new Span('Y', new NoopSpanContext(), []);
        $scope2 = $scopeManager->activate($span2, true);
        $span3 = new Span('X', new NoopSpanContext(), []);
        $scope3 = $scopeManager->activate($span3, true);

        $active = $scopeManager->getActive();
        self::assertTrue($active === $scope3);

        $res = $scopeManager->delActive($scope3);
        self::assertTrue($res == true);
        $active = $scopeManager->getActive();
        self::assertTrue($active === $scope2);

        $res = $scopeManager->delActive($scope2);
        self::assertTrue($res == true);
        $active = $scopeManager->getActive();
        self::assertTrue($active === $scope1);

        $res = $scopeManager->delActive($scope1);
        self::assertTrue($res == true);
        $active = $scopeManager->getActive();
        self::assertTrue($active === null);
    }

    public function testDelActiveReNestScopes(): void
    {
        $scopeManager = new ScopeManager();
        $span1 = new Span('A', new NoopSpanContext(), []);
        $scope1 = $scopeManager->activate($span1, true);
        $span2 = new Span('B', new NoopSpanContext(), []);
        $scope2 = $scopeManager->activate($span2, true);

        $active = $scopeManager->getActive();
        self::assertTrue($active === $scope2);

        // Remove scope2 so that scope1 is active
        $scopeManager->delActive($scope2);
        $active = $scopeManager->getActive();
        self::assertTrue($active === $scope1);

        // Add a new active scope3
        $span3 = new Span('C', new NoopSpanContext(), []);
        $scope3 = $scopeManager->activate($span3, true);
        $active = $scopeManager->getActive();
        self::assertTrue($active === $scope3);

        // Delete active scope3
        $scopeManager->delActive($scope3);
        $active = $scopeManager->getActive();
        self::assertTrue($active === $scope1);

        $scopeManager->delActive($scope1);
        $active = $scopeManager->getActive();
        self::assertTrue($active === null);
    }
}