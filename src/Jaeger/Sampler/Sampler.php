<?php

declare(strict_types=1);

namespace Jaeger\Sampler;

interface Sampler
{
    public function IsSampled(): bool;

    public function Close();

    public function getTags(): array;
}