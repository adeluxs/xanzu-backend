<?php

namespace Anand\LaravelPaytmWallet\Contracts;

interface Factory
{
    /**
     * Get Paytm Wallet Provider
     *
     * @param  string  $driver
     * @return Provider
     */
    public function driver($do = null);
}
