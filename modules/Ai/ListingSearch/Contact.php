<?php

namespace Modules\Ai\ListingSearch;

use Modules\Ai\ListingSearch\Traits\HasEnvironmentAwareErrors;

abstract class Contact
{
    use HasEnvironmentAwareErrors;

    public array $fullResponse = [];
    public array $data = [];

    abstract public function process(string $finalPrompt): mixed;
}
