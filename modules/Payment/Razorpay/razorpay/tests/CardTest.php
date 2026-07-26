<?php

namespace Razorpay\Tests;

class CardTest extends TestCase
{
    /**
     * Specify unique card id
     */
    private $cardId = 'card_LcQgzpfvWP0UKF';

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Fetch Card details
     */
    public function test_fetch_card()
    {
        $data = $this->api->card->fetch($this->cardId);

        $this->assertTrue(in_array($this->cardId, $data->toArray()));
    }
}
