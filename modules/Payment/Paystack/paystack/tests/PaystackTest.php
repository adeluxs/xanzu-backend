<?php

/*
 * This file is part of the Laravel Paystack package.
 *
 * (c) Prosper Otemuyiwa <prosperotemuyiwa@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Unicodeveloper\Paystack\Test;

use Mockery as m;
use PHPUnit\Framework\TestCase;

class PaystackTest extends TestCase
{
    protected $paystack;

    protected function setUp(): void
    {
        $this->paystack = m::mock('Unicodeveloper\Paystack\Paystack');
        $this->mock = m::mock('GuzzleHttp\Client');
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function test_all_customers_are_returned()
    {
        $array = $this->paystack->shouldReceive('getAllCustomers')->andReturn(['prosper']);

        $this->assertEquals('array', gettype([$array]));
    }

    public function test_all_transactions_are_returned()
    {
        $array = $this->paystack->shouldReceive('getAllTransactions')->andReturn(['transactions']);

        $this->assertEquals('array', gettype([$array]));
    }

    public function test_all_plans_are_returned()
    {
        $array = $this->paystack->shouldReceive('getAllPlans')->andReturn(['intermediate-plan']);

        $this->assertEquals('array', gettype([$array]));
    }
}
