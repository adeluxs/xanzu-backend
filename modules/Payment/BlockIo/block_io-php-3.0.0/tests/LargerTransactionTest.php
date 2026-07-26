<?php

use BlockIo\Client;
use PHPUnit\Framework\TestCase;

class LargerTransactionTest extends TestCase
{
    private $blockio;

    private $dtrust_keys;

    private $sweep_key;

    protected function setUp(): void
    {
        parent::setUp();
        $this->blockio = new Client('', 'd1650160bd8d2bb32bebd139d0063eb6063ffa2f9e4501ad', 2);
        $this->dtrust_keys = [
            'b515fd806a662e061b488e78e5d0c2ff46df80083a79818e166300666385c0a2',
            '1584b821c62ecdc554e185222591720d6fe651ed1b820d83f92cdc45c5e21f',
            '2f9090b8aa4ddb32c3b0b8371db1b50e19084c720c30db1d6bb9fcd3a0f78e61',
            '6c1cefdfd9187b36b36c3698c1362642083dcc1941dc76d751481d3aa29ca65',
        ];

        $key = $this->blockio->initKey();
        $key->fromWif('cTj8Ydq9LhZgttMpxb7YjYSqsZ2ZfmyzVprQgjEzAzQ28frQi4ML');

        $this->sweep_key = $key->getPrivateKey(); // in hex
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_dtrust_p2_s_h3_of5_keys195_inputs()
    {
        // test for partial signatures (P2SH) using 195 inputs

        $prepare_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/prepare_dtrust_transaction_response_P2SH_3of5_195inputs.json'), false);
        $create_and_sign_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/create_and_sign_transaction_response_dtrust_P2SH_3of5_195inputs.json'), true);

        $response = $this->blockio->create_and_sign_transaction($prepare_transaction_response, array_slice($this->dtrust_keys, 0, 3));
        $this->assertEquals($create_and_sign_transaction_response, $response);

    }

    public function test_dtrust_p2_s_h4_of5_keys195_inputs()
    {
        // test for full signatures (P2SH) using 195 inputs

        $prepare_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/prepare_dtrust_transaction_response_P2SH_4of5_195inputs.json'), false);
        $create_and_sign_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/create_and_sign_transaction_response_dtrust_P2SH_4of5_195inputs.json'), true);

        $response = $this->blockio->create_and_sign_transaction($prepare_transaction_response, array_slice($this->dtrust_keys, 0, 4));
        $this->assertEquals($create_and_sign_transaction_response, $response);

    }

    public function test_dtrust_p2_wsh_over_p2_s_h3_of5_keys251_inputs()
    {
        // test for partial signatures (P2WSH-over-P2SH) using 251 inputs

        $prepare_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/prepare_dtrust_transaction_response_P2WSH-over-P2SH_3of5_251inputs.json'), false);
        $create_and_sign_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/create_and_sign_transaction_response_dtrust_P2WSH-over-P2SH_3of5_251inputs.json'), true);

        $response = $this->blockio->create_and_sign_transaction($prepare_transaction_response, array_slice($this->dtrust_keys, 0, 3));
        $this->assertEquals($create_and_sign_transaction_response, $response);

    }

    public function test_dtrust_p2_wsh_over_p2_s_h3_of5_keys252_inputs()
    {
        // test for partial signatures (P2WSH-over-P2SH) using 252 inputs

        $prepare_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/prepare_dtrust_transaction_response_P2WSH-over-P2SH_3of5_252inputs.json'), false);
        $create_and_sign_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/create_and_sign_transaction_response_dtrust_P2WSH-over-P2SH_3of5_252inputs.json'), true);

        $response = $this->blockio->create_and_sign_transaction($prepare_transaction_response, array_slice($this->dtrust_keys, 0, 3));
        $this->assertEquals($create_and_sign_transaction_response, $response);

    }

    public function test_dtrust_p2_wsh_over_p2_s_h3_of5_keys253_inputs()
    {
        // test for partial signatures (P2WSH-over-P2SH) using 253 inputs

        $prepare_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/prepare_dtrust_transaction_response_P2WSH-over-P2SH_3of5_253inputs.json'), false);
        $create_and_sign_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/create_and_sign_transaction_response_dtrust_P2WSH-over-P2SH_3of5_253inputs.json'), true);

        $response = $this->blockio->create_and_sign_transaction($prepare_transaction_response, array_slice($this->dtrust_keys, 0, 3));
        $this->assertEquals($create_and_sign_transaction_response, $response);

    }

    public function test_dtrust_p2_wsh_over_p2_s_h4_of5_keys251_inputs()
    {
        // test for full signatures (P2WSH-over-P2SH) using 251 inputs

        $prepare_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/prepare_dtrust_transaction_response_P2WSH-over-P2SH_4of5_251inputs.json'), false);
        $create_and_sign_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/create_and_sign_transaction_response_dtrust_P2WSH-over-P2SH_4of5_251inputs.json'), true);

        $response = $this->blockio->create_and_sign_transaction($prepare_transaction_response, array_slice($this->dtrust_keys, 0, 4));
        $this->assertEquals($create_and_sign_transaction_response, $response);

    }

    public function test_dtrust_p2_wsh_over_p2_s_h4_of5_keys252_inputs()
    {
        // test for full signatures (P2WSH-over-P2SH) using 252 inputs

        $prepare_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/prepare_dtrust_transaction_response_P2WSH-over-P2SH_4of5_252inputs.json'), false);
        $create_and_sign_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/create_and_sign_transaction_response_dtrust_P2WSH-over-P2SH_4of5_252inputs.json'), true);

        $response = $this->blockio->create_and_sign_transaction($prepare_transaction_response, array_slice($this->dtrust_keys, 0, 4));
        $this->assertEquals($create_and_sign_transaction_response, $response);

    }

    public function test_dtrust_p2_wsh_over_p2_s_h4_of5_keys253_inputs()
    {
        // test for full signatures (P2WSH-over-P2SH) using 253 inputs

        $prepare_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/prepare_dtrust_transaction_response_P2WSH-over-P2SH_4of5_253inputs.json'), false);
        $create_and_sign_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/create_and_sign_transaction_response_dtrust_P2WSH-over-P2SH_4of5_253inputs.json'), true);

        $response = $this->blockio->create_and_sign_transaction($prepare_transaction_response, array_slice($this->dtrust_keys, 0, 4));
        $this->assertEquals($create_and_sign_transaction_response, $response);

    }

    public function test_dtrust_witness_v03_of5_keys251_inputs()
    {
        // test for partial signatures (WITNESS_V0) using 251 inputs

        $prepare_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/prepare_dtrust_transaction_response_WITNESS_V0_3of5_251inputs.json'), false);
        $create_and_sign_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/create_and_sign_transaction_response_dtrust_WITNESS_V0_3of5_251inputs.json'), true);

        $response = $this->blockio->create_and_sign_transaction($prepare_transaction_response, array_slice($this->dtrust_keys, 0, 3));
        $this->assertEquals($create_and_sign_transaction_response, $response);

    }

    public function test_dtrust_witness_v03_of5_keys252_inputs()
    {
        // test for partial signatures (WITNESS_V0) using 252 inputs

        $prepare_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/prepare_dtrust_transaction_response_WITNESS_V0_3of5_252inputs.json'), false);
        $create_and_sign_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/create_and_sign_transaction_response_dtrust_WITNESS_V0_3of5_252inputs.json'), true);

        $response = $this->blockio->create_and_sign_transaction($prepare_transaction_response, array_slice($this->dtrust_keys, 0, 3));
        $this->assertEquals($create_and_sign_transaction_response, $response);

    }

    public function test_dtrust_witness_v03_of5_keys253_inputs()
    {
        // test for partial signatures (WITNESS_V0) using 253 inputs

        $prepare_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/prepare_dtrust_transaction_response_WITNESS_V0_3of5_253inputs.json'), false);
        $create_and_sign_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/create_and_sign_transaction_response_dtrust_WITNESS_V0_3of5_253inputs.json'), true);

        $response = $this->blockio->create_and_sign_transaction($prepare_transaction_response, array_slice($this->dtrust_keys, 0, 3));
        $this->assertEquals($create_and_sign_transaction_response, $response);

    }

    public function test_dtrust_witness_v03_of5_keys251_outputs()
    {
        // test for partial signatures (WITNESS_V0) using 251 outputs

        $prepare_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/prepare_dtrust_transaction_response_witness_v0_3of5_251outputs.json'), false);
        $create_and_sign_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/create_and_sign_transaction_response_dtrust_witness_v0_3of5_251outputs.json'), true);

        $response = $this->blockio->create_and_sign_transaction($prepare_transaction_response, array_slice($this->dtrust_keys, 0, 3));
        $this->assertEquals($create_and_sign_transaction_response, $response);

    }

    public function test_dtrust_witness_v03_of5_keys252_outputs()
    {
        // test for partial signatures (WITNESS_V0) using 252 outputs

        $prepare_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/prepare_dtrust_transaction_response_witness_v0_3of5_252outputs.json'), false);
        $create_and_sign_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/create_and_sign_transaction_response_dtrust_witness_v0_3of5_252outputs.json'), true);

        $response = $this->blockio->create_and_sign_transaction($prepare_transaction_response, array_slice($this->dtrust_keys, 0, 3));
        $this->assertEquals($create_and_sign_transaction_response, $response);

    }

    public function test_dtrust_witness_v03_of5_keys253_outputs()
    {
        // test for partial signatures (WITNESS_V0) using 253 outputs

        $prepare_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/prepare_dtrust_transaction_response_witness_v0_3of5_253outputs.json'), false);
        $create_and_sign_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/create_and_sign_transaction_response_dtrust_witness_v0_3of5_253outputs.json'), true);

        $response = $this->blockio->create_and_sign_transaction($prepare_transaction_response, array_slice($this->dtrust_keys, 0, 3));
        $this->assertEquals($create_and_sign_transaction_response, $response);

    }

    public function test_dtrust_witness_v04_of5_keys251_inputs()
    {
        // test for full signatures (WITNESS_V0) using 251 inputs

        $prepare_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/prepare_dtrust_transaction_response_WITNESS_V0_4of5_251inputs.json'), false);
        $create_and_sign_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/create_and_sign_transaction_response_dtrust_WITNESS_V0_4of5_251inputs.json'), true);

        $response = $this->blockio->create_and_sign_transaction($prepare_transaction_response, array_slice($this->dtrust_keys, 0, 4));
        $this->assertEquals($create_and_sign_transaction_response, $response);

    }

    public function test_dtrust_witness_v04_of5_keys252_inputs()
    {
        // test for full signatures (WITNESS_V0) using 252 inputs

        $prepare_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/prepare_dtrust_transaction_response_WITNESS_V0_4of5_252inputs.json'), false);
        $create_and_sign_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/create_and_sign_transaction_response_dtrust_WITNESS_V0_4of5_252inputs.json'), true);

        $response = $this->blockio->create_and_sign_transaction($prepare_transaction_response, array_slice($this->dtrust_keys, 0, 4));
        $this->assertEquals($create_and_sign_transaction_response, $response);

    }

    public function test_dtrust_witness_v04_of5_keys253_inputs()
    {
        // test for full signatures (WITNESS_V0) using 253 inputs

        $prepare_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/prepare_dtrust_transaction_response_WITNESS_V0_4of5_253inputs.json'), false);
        $create_and_sign_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/create_and_sign_transaction_response_dtrust_WITNESS_V0_4of5_253inputs.json'), true);

        $response = $this->blockio->create_and_sign_transaction($prepare_transaction_response, array_slice($this->dtrust_keys, 0, 4));
        $this->assertEquals($create_and_sign_transaction_response, $response);

    }

    public function test_dtrust_witness_v04_of5_keys251_outputs()
    {
        // test for full signatures (WITNESS_V0) using 251 outputs

        $prepare_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/prepare_dtrust_transaction_response_witness_v0_4of5_251outputs.json'), false);
        $create_and_sign_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/create_and_sign_transaction_response_dtrust_witness_v0_4of5_251outputs.json'), true);

        $response = $this->blockio->create_and_sign_transaction($prepare_transaction_response, array_slice($this->dtrust_keys, 0, 4));
        $this->assertEquals($create_and_sign_transaction_response, $response);

    }

    public function test_dtrust_witness_v04_of5_keys252_outputs()
    {
        // test for full signatures (WITNESS_V0) using 252 outputs

        $prepare_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/prepare_dtrust_transaction_response_witness_v0_4of5_252outputs.json'), false);
        $create_and_sign_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/create_and_sign_transaction_response_dtrust_witness_v0_4of5_252outputs.json'), true);

        $response = $this->blockio->create_and_sign_transaction($prepare_transaction_response, array_slice($this->dtrust_keys, 0, 4));
        $this->assertEquals($create_and_sign_transaction_response, $response);

    }

    public function test_dtrust_witness_v04_of5_keys253_outputs()
    {
        // test for full signatures (WITNESS_V0) using 253 outputs

        $prepare_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/prepare_dtrust_transaction_response_witness_v0_4of5_253outputs.json'), false);
        $create_and_sign_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/create_and_sign_transaction_response_dtrust_witness_v0_4of5_253outputs.json'), true);

        $response = $this->blockio->create_and_sign_transaction($prepare_transaction_response, array_slice($this->dtrust_keys, 0, 4));
        $this->assertEquals($create_and_sign_transaction_response, $response);

    }

    public function test_p2_wsh_over_p2_s_h1of2_keys251_inputs()
    {
        // test for partial signatures (P2WSH-over-P2SH) using 251 inputs

        $prepare_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/prepare_transaction_response_P2WSH-over-P2SH_1of2_251inputs.json'), false);
        $create_and_sign_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/create_and_sign_transaction_response_P2WSH-over-P2SH_1of2_251inputs.json'), true);

        $response = $this->blockio->create_and_sign_transaction($prepare_transaction_response);
        $this->assertEquals($create_and_sign_transaction_response, $response);

    }

    public function test_p2_wsh_over_p2_s_h1of2_keys252_inputs()
    {
        // test for partial signatures (P2WSH-over-P2SH) using 252 inputs

        $prepare_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/prepare_transaction_response_P2WSH-over-P2SH_1of2_252inputs.json'), false);
        $create_and_sign_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/create_and_sign_transaction_response_P2WSH-over-P2SH_1of2_252inputs.json'), true);

        $response = $this->blockio->create_and_sign_transaction($prepare_transaction_response);
        $this->assertEquals($create_and_sign_transaction_response, $response);

    }

    public function test_p2_wsh_over_p2_s_h1of2_keys253_inputs()
    {
        // test for partial signatures (P2WSH-over-P2SH) using 253 inputs

        $prepare_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/prepare_transaction_response_P2WSH-over-P2SH_1of2_253inputs.json'), false);
        $create_and_sign_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/create_and_sign_transaction_response_P2WSH-over-P2SH_1of2_253inputs.json'), true);

        $response = $this->blockio->create_and_sign_transaction($prepare_transaction_response);
        $this->assertEquals($create_and_sign_transaction_response, $response);

    }

    public function test_p2_wsh_over_p2_s_h1of2_keys762_inputs()
    {
        // test for partial signatures (P2WSH-over-P2SH) using 762 inputs

        $prepare_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/prepare_transaction_response_P2WSH-over-P2SH_1of2_762inputs.json'), false);
        $create_and_sign_transaction_response = json_decode(file_get_contents(__DIR__.'/Data/json/create_and_sign_transaction_response_P2WSH-over-P2SH_1of2_762inputs.json'), true);

        $response = $this->blockio->create_and_sign_transaction($prepare_transaction_response);
        $this->assertEquals($create_and_sign_transaction_response, $response);

    }
}
