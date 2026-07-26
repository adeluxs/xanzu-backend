<?php

namespace Razorpay\Tests;

class SettlementTest extends TestCase
{
    /**
     * Specify unique settlement id
     * for example : setl_IAj6iuvvTATqOM
     */
    private $settlementId = 'setl_IAj6iuvvTATqOM';

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Create on-demand settlement
     */
    public function test_create_ondemand_settlement()
    {
        $data = $this->api->settlement->createOndemandSettlement(['amount' => 1221, 'settle_full_balance' => false, 'description' => 'Testing', 'notes' => ['notes_key_1' => 'Tea, Earl Grey, Hot', 'notes_key_2' => 'Tea, Earl Grey… decaf.']]);

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(in_array('settlement.ondemand', $data->toArray()));
    }

    /**
     * Fetch all settlements
     */
    public function test_all_settlements()
    {
        $data = $this->api->settlement->all();

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(in_array('collection', $data->toArray()));
    }

    /**
     * Fetch a settlement
     */
    public function test_fetch_settlement()
    {
        $data = $this->api->settlement->fetch($this->settlementId);

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(in_array('settlement', $data->toArray()));
    }

    /**
     * Settlement report for a month
     */
    public function test_reports()
    {
        $data = $this->api->settlement->reports(['year' => 2021, 'month' => 9]);

        $this->assertTrue(is_array($data->toArray()));

    }

    /**
     * Settlement recon
     */
    public function test_settlement_recon()
    {
        $data = $this->api->settlement->settlementRecon(['year' => 2021, 'month' => 9]);

        $this->assertTrue(is_array($data->toArray()));

        $this->assertArrayHasKey('items', $data);
    }

    /**
     * Fetch all on-demand settlements
     */
    public function test_fetch_all_ondemand_settlement()
    {
        $data = $this->api->settlement->fetchAllOndemandSettlement();

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(is_array($data['items']));
    }

    /**
     * Fetch on-demand settlement by ID
     */
    public function test_fetch_all_ondemand_settlement_by_id()
    {
        $data = $this->api->settlement->fetch($this->settlementId)->TestFetchAllOndemandSettlementById();

        $this->assertTrue(is_array($data->toArray()));

        $this->assertTrue(is_array($data['items']));
    }
}
