<?php

declare(strict_types=1);

namespace Jaeger;

use OpenTracing\Span as SpanInterface;

class Scope implements \OpenTracing\Scope
{
    /**
     * @var ScopeManager $scopeManager
     */
    private $scopeManager;

    /**
     * @var SpanInterface $span
     */
    private $span;

    private bool $finishSpanOnClose;

    public function __construct(
        ScopeManager $scopeManager,
        SpanInterface $span,
        bool $finishSpanOnClose
    ) {
        $this->scopeManager = $scopeManager;
        $this->span = $span;
        $this->finishSpanOnClose = $finishSpanOnClose;
    }

    public function close(): void
    {
        if ($this->finishSpanOnClose) {
            $this->span->finish();
        }

        $this->scopeManager->delActive($this);
    }

    public function getSpan(): SpanInterface
    {
        return $this->span;
    }
}
