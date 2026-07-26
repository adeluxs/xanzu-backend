<?php

namespace Razorpay\Tests;

use Razorpay\Api\Api;

class PaymentTest extends TestCase
{
    /**
     * Specify unique order id & payment id d
     * for example order_IEcrUMyevZFuCS & pay_IEczPDny6uzSnx
     */
    private $orderId = 'order_IEcrUMyevZFuCS';

    private $paymentId = 'pay_IEczPDny6uzSnx';

    private $OtpPaymentId = '';

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Fetch all payment
     */
    public function test_fetch_all_payment()
    {
        $data = $this->api->payment->all();

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(is_array($data['items']));
    }

    /**
     * Fetch a payment
     */
    public function test_fetch_payment()
    {
        $payment = $this->api->payment->all();

        if ($payment['count'] !== 0) {

            $data = $this->api->payment->fetch($payment['items'][0]['id']);

            $this->assertTrue(is_array($data->toArray()));

            $this->assertTrue(in_array('payment', $data->toArray()));
        }
    }

    /**
     * Fetch a payment
     */
    public function test_fetch_order_payment()
    {
        $data = $this->api->order->fetch($this->orderId)->payments();

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(is_array($data['items']));
    }

    /**
     * Update a payment
     */
    public function test_update_payment()
    {
        $data = $this->api->payment->fetch($this->paymentId)->edit(['notes' => ['key_1' => 'value1', 'key_2' => 'value2']]);

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(in_array('payment', $data->toArray()));
    }

    /**
     * Fetch card details with paymentId
     */
    public function test_fetch_card_with_payment_id()
    {
        $data = $this->api->payment->fetch($this->paymentId)->fetchCardDetails();

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(in_array('card', $data->toArray()));
    }

    /**
     * Fetch Payment Downtime Details
     */
    public function testfetch_payment_downtime()
    {
        $data = $this->api->payment->fetchPaymentDowntime();

        $this->assertTrue(is_array($data->toArray()));

        $this->assertArrayHasKey('count', $data->toArray());
    }

    /**
     * Fetch Payment Downtime Details
     */
    public function testfetch_payment_downtime_by_id()
    {
        $downtime = $this->api->payment->fetchPaymentDowntime();
        if (count($downtime['items']) > 0) {
            $data = $this->api->payment->fetchPaymentDowntimeById($downtime['items'][0]['id']);
            $this->assertTrue(is_array($data->toArray()));
        } else {
            $this->assertArrayHasKey('count', $downtime->toArray());
        }
    }

    /**
     * Otp Generate
     */
    public function test_otp_generate()
    {
        $api = new Api('key', '');

        $data = $api->payment->otpGenerate($OtpPaymentId);

        $this->assertTrue(is_array($data->toArray()));

        $this->assertArrayHasKey('razorpay_payment_id', $data->toArray());
    }

    /**
     * Otp Submit
     */
    public function test_otp_submit()
    {
        $data = $this->api->payment->fetch($paymentId)->otpSubmit(['otp' => '12345']);

        $this->assertTrue(is_array($data->toArray()));

        $this->assertArrayHasKey('razorpay_payment_id', $data->toArray());
    }

    /**
     * Otp Resend
     */
    public function test_otp_resend()
    {
        $data = $this->api->payment->fetch($paymentId)->otpResend();

        $this->assertTrue(is_array($data->toArray()));

        $this->assertArrayHasKey('razorpay_payment_id', $data->toArray());
    }
}
