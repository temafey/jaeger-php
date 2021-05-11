<?php

namespace Jaeger;

use OpenTracing\Span as SpanInterface;

class Scope implements \OpenTracing\Scope
{
    /**
     * @var MockScopeManager
     */
    private $scopeManager = null;

    /**
     * @var span
     */
    private $span = null;

    /**
     * @var bool
     */
    private $finishSpanOnClose;
    
    /**
     * Scope constructor.
     * @param ScopeManager $scopeManager
     * @param \OpenTracing\Span $span
     * @param bool $finishSpanOnClose
     */
    public function __construct(
        ScopeManager $scopeManager, 
        SpanInterface $span, 
        $finishSpanOnClose
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
