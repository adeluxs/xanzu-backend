<?php

namespace Modules\Ai\ReviewSummery;

use Modules\Ai\ReviewSummery\Traits\HasEnvironmentAwareErrors;

abstract class Contact
{
    use HasEnvironmentAwareErrors;

    public array $fullResponse = [];

    abstract public function process(string $finalPrompt): string;
}
