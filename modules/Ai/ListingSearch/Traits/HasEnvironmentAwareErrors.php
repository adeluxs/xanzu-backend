<?php

namespace Modules\Ai\ListingSearch\Traits;

trait HasEnvironmentAwareErrors
{
    protected function exceptionMessage(string $message, string $productionMessage = 'Something went wrong.'): string
    {
        return app()->isLocal() ? $message : $productionMessage;
    }
}
