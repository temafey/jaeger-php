<?php

declare(strict_types=1);

namespace Jaeger\Propagator;

use Jaeger\SpanContext;

interface Propagator
{
    public function inject(SpanContext $spanContext, string $format, &$carrier): void;

    public function extract(string $format, $carrier): ?SpanContext;
}
