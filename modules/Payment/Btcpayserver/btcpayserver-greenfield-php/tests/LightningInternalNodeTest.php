<?php

declare(strict_types=1);

namespace BTCPayServer\Tests;

use BTCPayServer\Client\BTCPayServerException;
use BTCPayServer\Client\LightningInternalNode;
use BTCPayServer\Result\LightningChannel;
use BTCPayServer\Result\LightningChannelList;
use BTCPayServer\Result\LightningInvoice;
use BTCPayServer\Result\LightningNode;
use BTCPayServer\Result\LightningNodeConnection;
use BTCPayServer\Result\LightningPayment;
use BTCPayServer\Util\PreciseNumber;

final class LightningInternalNodeTest extends BaseTest
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @group createLightningInvoice */
    public function test_it_can_create_a_lightning_invoice_and_returns_lightning_invoice_object(): void
    {
        $lightningClient = new LightningInternalNode($this->host, $this->apiKey);
        $lightningInvoice = $lightningClient->createLightningInvoice(
            'BTC',
            '100000', // milisats
            111111,
            'Test invoice description',
        );

        $this->assertInstanceOf(LightningInvoice::class, $lightningInvoice);

        $this->lightningInvoice = $lightningInvoice;

        $this->assertIsString($lightningInvoice->getId());
        $this->assertIsString($lightningInvoice->getStatus());
        $this->assertIsString($lightningInvoice->getBolt11());

        // If the lightning invoice is paid, assert the paid at is an int
        if ($lightningInvoice->getPaidAt()) {
            $this->assertIsInt($lightningInvoice->getPaidAt());
        }

        $this->assertIsInt($lightningInvoice->getExpiresAt());
        $this->assertInstanceOf(PreciseNumber::class, $lightningInvoice->getAmount());

        // If the lightning invoice is paid, assert the amount received is a PreciseNumber
        if ($lightningInvoice->getAmountReceived()) {
            $this->assertInstanceOf(PreciseNumber::class, $lightningInvoice->getAmountReceived());
        }
    }

    /** @group payLightningInvoice */
    public function test_it_receives_lightning_payment_object_after_paying_lightning_invoice_with_all_getters(): void
    {
        $this->markTestSkipped('Requires a new invoice on each test run');
        $lightningClient = new LightningInternalNode($this->host, $this->apiKey);
        $bolt11 = '';

        $lightningPayment = $lightningClient->payLightningInvoice(
            cryptoCode: 'BTC',
            BOLT11: $bolt11,
            maxFeePercent: '0.1',
            maxFeeFlat: '10',
        );

        $this->assertInstanceOf(LightningPayment::class, $lightningPayment);

        // There is a bug in Greenfield API that is returning null values on everything except total and fee amounts.
        // Uncomment these lines when the bug is fixed.
        // https://github.com/btcpayserver/btcpayserver/issues/4229

        // $this->assertIsString($lightningPayment->getId());
        // $this->assertIsString($lightningPayment->getStatus());
        // $this->assertIsString($lightningPayment->getBolt11());
        // $this->assertIsString($lightningPayment->getPaymentHash());
        // $this->assertIsString($lightningPayment->getPreimage());
        // $this->assertIsInt($lightningPayment->getCreatedAt());

        $this->assertInstanceOf(PreciseNumber::class, $lightningPayment->getTotalAmount());
        $this->assertInstanceOf(PreciseNumber::class, $lightningPayment->getFeeAmount());
    }

    /** @group connectToLightningNode */
    public function test_it_can_connect_to_a_lightning_node_and_returns_lightning_node_connection_object(): void
    {
        $this->markTestSkipped('This test is skipped because I always get 503.');
        $lightningClient = new LightningInternalNode($this->host, $this->apiKey);

        try {
            $lightningNodeConnection = $lightningClient->connectToLightningNode(
                cryptoCode: 'BTC',
                nodeURI: $this->nodeUri,
            );

            $this->assertInstanceOf(LightningNodeConnection::class, $lightningNodeConnection);
        } catch (BTCPayServerException $e) {
            exit($e->getMessage());
        }
    }

    /** @group getNodeInformation */
    public function test_it_can_get_node_information_and_returns_lightning_node_information_object(): void
    {
        $lightningClient = new LightningInternalNode($this->host, $this->apiKey);
        $lightningNodeInformation = $lightningClient->getNodeInformation(
            'BTC',
        );

        $this->assertInstanceOf(LightningNode::class, $lightningNodeInformation);

        $this->assertIsArray($lightningNodeInformation->getNodeURIs());
        $this->assertIsInt($lightningNodeInformation->getBlockHeight());
        $this->assertIsString($lightningNodeInformation->getAlias());
        $this->assertIsString($lightningNodeInformation->getColor());
        $this->assertIsString($lightningNodeInformation->getVersion());
        $this->assertIsInt($lightningNodeInformation->getPeersCount());
        $this->assertIsInt($lightningNodeInformation->getPendingChannelsCount());
        $this->assertIsInt($lightningNodeInformation->getActiveChannelsCount());
        $this->assertIsInt($lightningNodeInformation->getInactiveChannelsCount());
    }

    /** @group getChannels */
    public function test_it_can_get_channels_and_returns_lightning_channel_list_object(): void
    {
        $lightningClient = new LightningInternalNode($this->host, $this->apiKey);
        $lightningChannels = $lightningClient->getChannels(
            'BTC',
        );

        $this->assertInstanceOf(LightningChannelList::class, $lightningChannels);

        $this->assertIsArray($lightningChannels->all());

        foreach ($lightningChannels->all() as $channel) {
            $this->assertInstanceOf(LightningChannel::class, $channel);
            $this->assertIsString($channel->getRemoteNode());
            $this->assertIsString($channel->getChannelPoint());
            $this->assertIsString($channel->getCapacity());
            $this->assertIsString($channel->getLocalBalance());
            $this->assertIsBool($channel->isActive());
            $this->assertIsBool($channel->isPublic());
        }
    }

    /** @group getDepositAddress */
    public function test_it_can_get_a_new_deposit_address(): void
    {
        $lightningClient = new LightningInternalNode($this->host, $this->apiKey);
        $depositAddress = $lightningClient->getDepositAddress(
            'BTC',
        );

        $this->assertIsString($depositAddress);
    }

    /** @group getLightningInvoice */
    public function test_it_can_get_an_invoice_and_returns_lightning_invoice_object(): void
    {
        $lightningClient = new LightningInternalNode($this->host, $this->apiKey);

        $getLightningInvoice = $lightningClient->createLightningInvoice(
            'BTC',
            '100000', // milisats
            111111,
            'Test invoice description',
        );

        $lightningInvoice = $lightningClient->getLightningInvoice(
            'BTC',
            $getLightningInvoice->getId(),
        );

        $this->assertInstanceOf(LightningInvoice::class, $lightningInvoice);

        $this->assertIsString($lightningInvoice->getId());
        $this->assertIsString($lightningInvoice->getStatus());
        $this->assertIsString($lightningInvoice->getBolt11());

        // If the invoice get Paid at is not null, assert it's int
        if ($lightningInvoice->getPaidAt() !== null) {
            $this->assertIsInt($lightningInvoice->getPaidAt());
        }

        $this->assertIsInt($lightningInvoice->getExpiresAt());
        $this->assertInstanceOf(PreciseNumber::class, $lightningInvoice->getAmount());

        // If the invoice get Paid amount is not null, assert it's PreciseNumber
        if ($lightningInvoice->getAmountReceived() !== null) {
            $this->assertInstanceOf(PreciseNumber::class, $lightningInvoice->getAmountReceived());
        }
    }
}
