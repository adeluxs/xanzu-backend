<?php

namespace Razorpay\Tests;

class OrdersTest extends TestCase
{
    /**
     * Specify unique order id
     * for example order_IEfF1OrQbqxYJq
     */
    private $orderId = 'order_IEfF1OrQbqxYJq';

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Create order
     */
    public function test_create_order()
    {
        $data = $this->api->order->create(['receipt' => '123', 'amount' => 100, 'currency' => 'INR', 'notes' => ['key1' => 'value3', 'key2' => 'value2']]);

        $this->assertTrue(is_array($data->toArray()));

        $this->assertArrayHasKey('id', $data->toArray());
    }

    /**
     * Fetch all orders
     */
    public function test_all_orders()
    {
        $data = $this->api->order->all();

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(is_array($data['items']));
    }

    /**
     * Fetch particular order
     */
    public function test_fetch_order()
    {
        $data = $this->api->order->fetch($this->orderId);

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(in_array('order', $data->toArray()));
    }

    /**
     * Fetch payments for an order
     */
    public function test_order_fetch_by_id()
    {
        $data = $this->api->order->fetch($this->orderId)->payments();

        $this->assertTrue(is_array($data->toArray()));

    }

    /**
     * Update Order
     */
    public function test_update_order()
    {
        $data = $this->api->order->fetch($this->orderId)->edit(['notes' => ['notes_key_1' => 'Beam me up Scotty. 1', 'notes_key_2' => 'Engage']]);

        $this->assertTrue(is_array($data->toArray()));

        $this->assertArrayHasKey('id', $data->toArray());

    }
}
