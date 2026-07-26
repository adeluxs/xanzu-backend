<?php

namespace Razorpay\Tests;

class ItemTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Create item
     */
    public function testcreate()
    {
        $data = $this->api->Item->create([
            'name' => 'Book / English August',
            'description' => 'An indian story, Booker prize winner.',
            'amount' => 20000,
            'currency' => 'INR',
        ]);

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(in_array('id', $data->toArray()));
    }

    /**
     * Fetch all orders
     */
    public function test_all_items()
    {
        $data = $this->api->Item->all();

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(is_array($data['items']));
    }

    /**
     * Fetch particular item
     */
    public function testfetch_item()
    {
        $item = $this->api->Item->create([
            'name' => 'Book / English August',
            'description' => 'An indian story, Booker prize winner.',
            'amount' => 20000,
            'currency' => 'INR',
        ]);

        $data = $this->api->Item->fetch($item->id);

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(in_array($item->id, $data->toArray()));
    }

    /**
     * Update item
     */
    public function test_update()
    {
        $item = $this->api->Item->create([
            'name' => 'Book / English August',
            'description' => 'An indian story, Booker prize winner.',
            'amount' => 20000,
            'currency' => 'INR',
        ]);

        $data = $this->api->Item->fetch($item->id)->edit([
            'name' => 'Book / English August',
            'description' => 'An indian story, Booker prize winner.',
            'amount' => 20000,
            'currency' => 'INR',
        ]);

        $this->assertTrue(is_array($data->toArray()));

    }

    /**
     * Delete item
     */
    public function test_delete()
    {
        $item = $this->api->Item->create([
            'name' => 'Book / English August',
            'description' => 'An indian story, Booker prize winner.',
            'amount' => 20000,
            'currency' => 'INR',
        ]);

        $data = $this->api->Item->fetch($item->id)->delete();

        $this->assertTrue(is_array($data->toArray()));
    }
}
