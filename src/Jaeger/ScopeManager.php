<?php

declare(strict_types=1);

namespace Jaeger;

use OpenTracing\Scope as ScopeInterface;
use OpenTracing\ScopeManager as ScopeManagerInterface;
use OpenTracing\Span as SpanInterface;

class ScopeManager implements ScopeManagerInterface
{
    private array $scopes = [];

    /**
     * {@inheritdoc}
     */
    public function activate(
        SpanInterface $span,
        bool $finishSpanOnClose = self::DEFAULT_FINISH_SPAN_ON_CLOSE
    ): ScopeInterface {
        $scope = new Scope($this, $span, $finishSpanOnClose);
        $this->scopes[] = $scope;

        return $scope;
    }


    /**
     * Get last active Scope
     */
    public function getActive(): ?ScopeInterface
    {
        if (empty($this->scopes)) {
            return null;
        }

        return $this->scopes[count($this->scopes) - 1];
    }

    /**
     * Del specific Scope
     */
    public function delActive(ScopeInterface $scope): bool
    {
        $scopeLength = count($this->scopes);
        if ($scopeLength <= 0) {
            return false;
        }

        for ($i = 0; $i < $scopeLength; $i++) {
            if ($scope === $this->scopes[$i]) {
                array_splice($this->scopes, $i, 1);
            }
        }

        return true;
    }
}
