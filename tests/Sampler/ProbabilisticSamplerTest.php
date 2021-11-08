<?php

declare(strict_types=1);

namespace JagerPhp\Tests\Sampler;

use Jaeger\Constants;
use Jaeger\Sampler\ProbabilisticSampler;
use PHPUnit\Framework\TestCase;

class ProbabilisticSamplerTest extends TestCase
{
    public function testProbabilisticSampler(): void
    {
        $sample = new ProbabilisticSampler(0.0001);
        self::assertTrue($sample->IsSampled() !== null);
    }

    public function testConstSamplerGetTag(): void
    {
        $sample = new ProbabilisticSampler(0.0001);
        $tags = $sample->getTags();
        self::assertTrue($tags[Constants\SAMPLER_TYPE_TAG_KEY] == 'probabilistic');
    }
}