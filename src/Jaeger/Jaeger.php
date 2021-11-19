<?php

declare(strict_types=1);

namespace Jaeger;

use Jaeger\Propagator\Propagator as PropagatorInterface;
use Jaeger\Reporter\Reporter as ReporterInterface;
use Jaeger\Sampler\Sampler as SamplerInterface;
use OpenTracing\Reference;
use OpenTracing\Scope as ScopeInterface;
use OpenTracing\ScopeManager as ScopeManagerInterface;
use OpenTracing\Span as SpanInterface;
use OpenTracing\SpanContext as SpanContextInterface;
use OpenTracing\StartSpanOptions;
use OpenTracing\Tracer as TracerInterface;
use OpenTracing\UnsupportedFormatException;
use const OpenTracing\Formats\TEXT_MAP;

class Jaeger implements TracerInterface
{
    private const SERVER_NAME_UNKNOWN = 'unknown_server';

    private const FORMAT_TEXT_MAP = TEXT_MAP;

    private ReporterInterface $reporter;

    private SamplerInterface $sampler;

    private ScopeManagerInterface $scopeManager;

    public ?PropagatorInterface $propagator;

    public array $spans = [];

    public array $tags = [];

    public string $serverName;

    private bool $gen128bit = false;

    public $processThrift = '';

    public $process = null;

    public function __construct(
        string $serverName = self::SERVER_NAME_UNKNOWN,
        ReporterInterface $reporter,
        SamplerInterface $sampler,
        ScopeManagerInterface $scopeManager
    ) {
        $this->serverName = $serverName;
        $this->reporter = $reporter;
        $this->sampler = $sampler;
        $this->scopeManager = $scopeManager;
        $this->setTags($this->sampler->getTags());
        $this->setTags($this->getEnvTags());
    }

    public function setTags(array $tags = []): void
    {
        if (!empty($tags)) {
            $this->tags = array_merge($this->tags, $tags);
        }
    }

    /**
     * Init span info
     *
     * @param array $options
     */
    public function startSpan(string $operationName, $options = []): SpanInterface
    {
        if (!($options instanceof StartSpanOptions)) {
            $options = StartSpanOptions::create($options);
        }

        $parentSpan = $this->getParentSpanContext($options);
        if ($parentSpan == null || !$parentSpan->traceIdLow) {
            $low = $this->generateId();
            $spanId = $low;
            $flags = $this->sampler->IsSampled();
            $spanContext = new SpanContext($spanId, 0, $flags, null, 0);
            $spanContext->traceIdLow = $low;
            if ($this->gen128bit) {
                $spanContext->traceIdHigh = $this->generateId();
            }
        } else {
            $spanContext = new SpanContext(
                $this->generateId(),
                $parentSpan->spanId,
                $parentSpan->flags,
                $parentSpan->baggage,
                0
            );
            $spanContext->traceIdLow = $parentSpan->traceIdLow;
            if ($parentSpan->traceIdHigh) {
                $spanContext->traceIdHigh = $parentSpan->traceIdHigh;
            }
        }

        $startTime = $options->getStartTime() ? intval($options->getStartTime() * 1000000) : null;
        $span = new Span($operationName, $spanContext, $options->getReferences(), $startTime);

        if (!empty($options->getTags())) {
            foreach ($options->getTags() as $k => $tag) {
                $span->setTag($k, $tag);
            }
        }
        if ($spanContext->isSampled() == 1) {
            $this->spans[] = $span;
        }

        return $span;
    }

    public function setPropagator(PropagatorInterface $propagator)
    {
        $this->propagator = $propagator;
    }

    /**
     * @inheritdoc
     *
     * @param SpanContextInterface|SpanContext $spanContext
     */
    public function inject(SpanContextInterface $spanContext, string $format, &$carrier): void
    {
        if ($format == TEXT_MAP) {
            $this->propagator->inject($spanContext, $format, $carrier);
        } else {
            throw UnsupportedFormatException::forFormat($format);
        }
    }

    /**
     * @inheritdoc
     */
    public function extract(string $format, $carrier): ?SpanContextInterface
    {
        if (self::FORMAT_TEXT_MAP === $format) {
            return $this->propagator->extract($format, $carrier);
        } else {
            throw UnsupportedFormatException::forFormat($format);
        }
    }

    public function getSpans(): array
    {
        return $this->spans;
    }

    public function reportSpan(): void
    {
        if ($this->spans) {
            $this->reporter->report($this);
            $this->spans = [];
        }
    }

    /**
     * @inheritdoc
     */
    public function getScopeManager(): ScopeManagerInterface
    {
        return $this->scopeManager;
    }

    /**
     * @inheritdoc
     */
    public function getActiveSpan(): ?SpanInterface
    {
        $activeScope = $this->getScopeManager()->getActive();
        if ($activeScope === null) {
            return null;
        }

        return $activeScope->getSpan();
    }

    /**
     * @inheritdoc
     */
    public function startActiveSpan(string $operationName, $options = []): ScopeInterface
    {
        if (false === $options instanceof StartSpanOptions) {
            $options = StartSpanOptions::create($options);
        }

        $parentSpan = $this->getParentSpanContext($options);
        if ($parentSpan === null && $this->getActiveSpan() !== null) {
            $parentContext = $this->getActiveSpan()->getContext();
            $options = $options->withParent($parentContext);
        }

        $span = $this->startSpan($operationName, $options);

        return $this->getScopeManager()->activate($span, $options->shouldFinishSpanOnClose());
    }

    private function getParentSpanContext(StartSpanOptions $options): ?SpanContextInterface
    {
        $references = $options->getReferences();
        $parentSpan = null;

        foreach ($references as $ref) {
            $parentSpan = $ref->getSpanContext();
            if ($ref->isType(Reference::CHILD_OF)) {
                return $parentSpan;
            }
        }

        if (null === $parentSpan) {
            return null;
        }
        if (
            $parentSpan->isValid()
            || (false === $parentSpan->isTraceIdValid() && $parentSpan->debugId)
            || 0 < count($parentSpan->baggage)
        ) {
            return $parentSpan;
        }

        return null;
    }

    public function getEnvTags(): array
    {
        $tags = [];
        if (isset($_SERVER['JAEGER_TAGS']) && $_SERVER['JAEGER_TAGS'] != '') {
            $envTags = explode(',', $_SERVER['JAEGER_TAGS']);

            foreach ($envTags as $envTag) {
                list($key, $value) = explode('=', $envTag);
                $tags[$key] = $value;
            }
        }

        return $tags;
    }

    public function gen128bit(): void
    {
        $this->gen128bit = true;
    }

    /**
     * Send Span and close Reporter
     */
    public function flush(): void
    {
        $this->reportSpan();
        $this->reporter->close();
    }

    private function generateId(): string
    {
        return microtime(true) * 10000 . rand(10000, 99999);
    }
}
