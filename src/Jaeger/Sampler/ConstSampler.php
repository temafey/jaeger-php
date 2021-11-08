<?php

declare(strict_types=1);

namespace Jaeger\Sampler;

use const Jaeger\Constants\SAMPLER_PARAM_TAG_KEY;
use const Jaeger\Constants\SAMPLER_TYPE_TAG_KEY;

class ConstSampler implements Sampler
{
    private bool $decision;

    private array $tags = [];

    public function __construct($decision = true)
    {
        $this->decision = $decision;
        $this->tags[SAMPLER_TYPE_TAG_KEY] = 'const';
        $this->tags[SAMPLER_PARAM_TAG_KEY] = $decision;
    }

    public function IsSampled(): bool
    {
        return $this->decision;
    }

    public function Close()
    {
        //nothing to do
    }

    public function getTags(): array
    {
        return $this->tags;
    }
}