<?php

namespace Razorpay\Api\Test;

include 'Razorpay.php';

use Razorpay\Api\Api;

class RazorpayTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->api = new Api($_SERVER['KEY_ID'], $_SERVER['KEY_SECRET']);
    }

    public function test_api_access()
    {
        $this->assertInstanceOf('Razorpay\Api\Api', $this->api);
    }

    public function test_requests()
    {
        $this->assertTrue(class_exists('\Requests'));
    }
}
