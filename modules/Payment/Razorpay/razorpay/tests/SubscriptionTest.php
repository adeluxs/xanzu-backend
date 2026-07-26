<?php

namespace Razorpay\Tests;

class SubscriptionTest extends TestCase
{
    /**
     * Specify unique subscription id & plan id
     * for example : sub_IEKtBfPIqTHLWd & plan_IEeswu4zFBRGwi
     */
    private $subscriptionId = 'sub_IEllLOZcf0PODu';

    private $plan = 'plan_IEeswu4zFBRGwi';

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Create a Subscription Link
     */
    public function test_create_subscription()
    {
        $data = $this->api->subscription->create(['plan_id' => $this->plan, 'customer_notify' => 1, 'quantity' => 1, 'total_count' => 6, 'addons' => [['item' => ['name' => 'Delivery charges', 'amount' => 3000, 'currency' => 'INR']]], 'notes' => ['key1' => 'value3', 'key2' => 'value2']]);

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(in_array('id', $data->toArray()));
    }

    /**
     * Fetch Subscription Link by ID
     */
    public function test_subscription_fetch_id()
    {
        $data = $this->api->subscription->fetch($this->subscriptionId);

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(in_array('plan_id', $data->toArray()));
    }

    /**
     * Pause a Subscription
     */
    public function test_pause_subscription()
    {

        $data = $this->api->subscription->fetch($this->subscriptionId)->pause(['pause_at' => 'now']);

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(in_array('id', $data->toArray()));

        $this->assertTrue($data['status'] == 'paused');

    }

    /**
     * Resume a Subscription
     */
    public function test_resume_subscription()
    {
        $data = $this->api->subscription->fetch($this->subscriptionId)->resume(['resume_at' => 'now']);

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(in_array('id', $data->toArray()));

    }

    /**
     * Update a Subscription
     */
    public function test_update_subscription()
    {
        $data = $this->api->subscription->fetch($this->subscriptionId)->update(['schedule_change_at' => 'cycle_end', 'quantity' => 2]);

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(in_array('customer_id', $data->toArray()));
    }

    /**
     * Fetch Details of a Pending Update
     */
    public function test_pending_update()
    {
        $data = $this->api->subscription->fetch($this->subscriptionId)->pendingUpdate();

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(in_array('id', $data->toArray()));
    }

    /**
     * Cancel an Update
     */
    public function test_cancel_update()
    {
        $data = $this->api->subscription->fetch($this->subscriptionId)->cancelScheduledChanges();

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(in_array('id', $data->toArray()));
    }

    /**
     * Fetch All Invoices for a Subscription
     */
    public function test_subscription_invoices()
    {
        $data = $this->api->invoice->all(['subscription_id' => $this->subscriptionId]);

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(is_array($data['items']));
    }

    /**
     * Fetch all Add-ons
     */
    public function test_fetch_addons()
    {
        $data = $this->api->addon->fetchAll();

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(is_array($data['items']));
    }

    /**
     * Fetch all subscriptions
     */
    public function test_fetch_all_subscriptions()
    {
        $data = $this->api->subscription->all();

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(is_array($data['items']));
    }
}
