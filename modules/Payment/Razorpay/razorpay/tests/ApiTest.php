<?php

namespace Razorpay\Tests;

use Razorpay\Api\Request;

class ApiTest extends TestCase
{
    private $title = 'codecov_test';

    private $url = 'https://api.razorpay.com';

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Get app details
     */
    public function test_get_app_details()
    {
        $this->api->setAppDetails($this->title);

        $data = $this->api->getAppsDetails();

        $this->assertTrue(is_array($data));

        $this->assertTrue($this->title == $data[0]['title']);
    }

    /**
     * Get app details
     */
    public function test_set_base_url()
    {
        $this->api->setBaseUrl($this->url);

        $data = $this->api->getBaseUrl();

        $this->assertTrue($this->url == $data);

    }

    public function test_getkey()
    {
        $data = $this->api->getKey();

        $this->assertTrue(strlen($data) > 0);
    }

    public function test_get_secret()
    {
        $data = $this->api->getSecret();
        $this->assertTrue(strlen($data) > 0);
    }

    public function test_full_url()
    {
        $pattern = '/^(https?:\/\/)?([a-z0-9-]+\.)+[a-z]{2,}(\/.*)?$/i';
        $url = $this->api->getFullUrl($this->api->getBaseUrl().'orders', 'v1');
        $this->assertTrue(preg_match($pattern, $url, $matches) == true);
    }

    /**
     * @covers \Request
     */
    public function testgetheader()
    {
        $data = Request::getHeaders();
        $this->assertTrue(is_array($data));
    }
}
