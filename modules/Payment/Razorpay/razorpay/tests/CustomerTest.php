<?php

namespace Razorpay\Tests;

class CustomerTest extends TestCase
{
    /**
     * Specify unique customer id
     * for example cust_IEfAt3ruD4OEzo
     */
    private $customerId = 'cust_IEfAt3ruD4OEzo';

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Create customer
     */
    public function test_create_customer()
    {
        $data = $this->api->customer->create(['name' => 'Razorpay User 38', 'email' => 'customer38@razorpay.com', 'fail_existing' => '0']);

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(in_array('customer', $data->toArray()));
    }

    /**
     * Edit customer
     */
    public function test_edit_customer()
    {
        $data = $this->api->customer->fetch($this->customerId)->edit(['name' => 'Razorpay User 21', 'contact' => '9123456780']);

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(in_array($this->customerId, $data->toArray()));
    }

    /**
     * Fetch customer All
     */
    public function test_fetch_all()
    {
        $data = $this->api->customer->all();

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(is_numeric($data->count()));

        $this->assertTrue(is_array($data['items']));
    }

    /**
     * Fetch a customer
     */
    public function test_fetch_customer()
    {
        $data = $this->api->customer->fetch($this->customerId);

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(in_array($this->customerId, $data->toArray()));
    }
}
