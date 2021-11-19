<?php

declare(strict_types=1);

namespace Jaeger\Sampler;

use const Jaeger\Constants\SAMPLER_PARAM_TAG_KEY;
use const Jaeger\Constants\SAMPLER_TYPE_TAG_KEY;

class ProbabilisticSampler implements Sampler
{
    // min 0, max 1
    private float $rate;

    private array $tags = [];

    public function __construct(float $rate = 0.0001)
    {
        $this->rate = $rate;
        $this->tags[SAMPLER_TYPE_TAG_KEY] = 'probabilistic';
        $this->tags[SAMPLER_PARAM_TAG_KEY] = $rate;
    }

    public function IsSampled(): bool
    {
        $max = (int)(1 / $this->rate);
        if (1 === mt_rand(1, $max)) {
            return true;
        }

        return false;
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
