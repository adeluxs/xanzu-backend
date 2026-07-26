<?php

namespace SecurionPay;

use SecurionPay\Connection\Connection;
use SecurionPay\Connection\CurlConnection;
use SecurionPay\Exception\SecurionPayException;
use SecurionPay\Request\BlacklistRuleListRequest;
use SecurionPay\Request\BlacklistRuleRequest;
use SecurionPay\Request\CaptureRequest;
use SecurionPay\Request\CardListRequest;
use SecurionPay\Request\CardRequest;
use SecurionPay\Request\CardUpdateRequest;
use SecurionPay\Request\ChargeListRequest;
use SecurionPay\Request\ChargeRequest;
use SecurionPay\Request\ChargeUpdateRequest;
use SecurionPay\Request\CheckoutRequest;
use SecurionPay\Request\CreditListRequest;
use SecurionPay\Request\CreditRequest;
use SecurionPay\Request\CreditUpdateRequest;
use SecurionPay\Request\CrossSaleOfferListRequest;
use SecurionPay\Request\CrossSaleOfferRequest;
use SecurionPay\Request\CrossSaleOfferUpdateRequest;
use SecurionPay\Request\CustomerListRequest;
use SecurionPay\Request\CustomerRequest;
use SecurionPay\Request\CustomerUpdateRequest;
use SecurionPay\Request\DisputeListRequest;
use SecurionPay\Request\DisputeUpdateRequest;
use SecurionPay\Request\EventListRequest;
use SecurionPay\Request\FileUploadListRequest;
use SecurionPay\Request\FraudWarningListRequest;
use SecurionPay\Request\PaymentMethodListRequest;
use SecurionPay\Request\PayoutListRequest;
use SecurionPay\Request\PayoutTransactionListRequest;
use SecurionPay\Request\PlanListRequest;
use SecurionPay\Request\PlanRequest;
use SecurionPay\Request\PlanUpdateRequest;
use SecurionPay\Request\RefundListRequest;
use SecurionPay\Request\RefundRequest;
use SecurionPay\Request\SubscriptionCancelRequest;
use SecurionPay\Request\SubscriptionListRequest;
use SecurionPay\Request\SubscriptionRequest;
use SecurionPay\Request\SubscriptionUpdateRequest;
use SecurionPay\Request\TokenRequest;
use SecurionPay\Response\BlacklistRule;
use SecurionPay\Response\Card;
use SecurionPay\Response\Charge;
use SecurionPay\Response\Credit;
use SecurionPay\Response\CrossSaleOffer;
use SecurionPay\Response\Customer;
use SecurionPay\Response\DeleteResponse;
use SecurionPay\Response\Dispute;
use SecurionPay\Response\Event;
use SecurionPay\Response\FileUpload;
use SecurionPay\Response\ListResponse;
use SecurionPay\Response\PaymentMethod;
use SecurionPay\Response\Payout;
use SecurionPay\Response\Plan;
use SecurionPay\Response\Refund;
use SecurionPay\Response\Subscription;
use SecurionPay\Response\Token;
use SecurionPay\Util\ObjectSerializer;

class SecurionPayGateway
{
    const VERSION = '2.5.0';

    const DEFAULT_ENDPOINT = 'https://api.securionpay.com';

    const DEFAULT_UPLOADS_ENDPOINT = 'https://uploads.securionpay.com/';

    private $objectSerializer;

    /**
     * @var Connection
     */
    private $connection;

    private $privateKey;

    private $endpoint = self::DEFAULT_ENDPOINT;

    private $uploadsEndpoint = self::DEFAULT_UPLOADS_ENDPOINT;

    private $userAgent;

    public function __construct($privateKey = null, ?Connection $connection = null)
    {
        $this->objectSerializer = new ObjectSerializer;

        $this->privateKey = $privateKey;
        $this->connection = $connection ? $connection : new CurlConnection;
    }

    /**
     * @param  ChargeRequest  $request
     * @return Charge
     */
    public function createCharge($request)
    {
        return $this->post('/charges', $request, '\SecurionPay\Response\Charge');
    }

    /**
     * @param  CaptureRequest  $request
     * @return Charge
     */
    public function captureCharge($request)
    {
        return $this->post('/charges/{chargeId}/capture', $request, '\SecurionPay\Response\Charge');
    }

    /**
     * @param  string  $chargeId
     * @return Charge
     */
    public function retrieveCharge($chargeId)
    {
        return $this->get("/charges/{$chargeId}", '\SecurionPay\Response\Charge');
    }

    /**
     * @param  ChargeUpdateRequest  $request
     * @return Charge
     */
    public function updateCharge($request)
    {
        return $this->post('/charges/{chargeId}', $request, '\SecurionPay\Response\Charge');
    }

    /**
     * @param  RefundRequest  $request
     * @return Refund
     *
     * @deprecated For backward compatibility only. Use "createRefund($request)".
     */
    public function refundCharge($request)
    {
        return $this->createRefund($request);
    }

    /**
     * @param  ChargeListRequest  $request
     * @return ListResponse
     */
    public function listCharges($request = null)
    {
        return $this->getList('/charges', $request, '\SecurionPay\Response\Charge');
    }

    /**
     * @param  CustomerRequest  $request
     * @return Customer
     */
    public function createCustomer($request)
    {
        return $this->post('/customers', $request, '\SecurionPay\Response\Customer');
    }

    /**
     * @param  string  $customerId
     * @return Customer
     */
    public function retrieveCustomer($customerId)
    {
        return $this->get("/customers/{$customerId}", '\SecurionPay\Response\Customer');
    }

    /**
     * @param  CustomerUpdateRequest  $request
     * @return Customer
     */
    public function updateCustomer($request)
    {
        return $this->post('/customers/{customerId}', $request, '\SecurionPay\Response\Customer');
    }

    /**
     * @param  string  $customerId
     * @return DeleteResponse
     */
    public function deleteCustomer($customerId)
    {
        return $this->delete("/customers/{$customerId}", null, '\SecurionPay\Response\DeleteResponse');
    }

    /**
     * @param  CustomerListRequest  $request
     * @return ListResponse
     */
    public function listCustomers($request = null)
    {
        return $this->getList('/customers', $request, '\SecurionPay\Response\Customer');
    }

    /**
     * @param  CardRequest  $request
     * @return Card
     */
    public function createCard($request)
    {
        return $this->post('/customers/{customerId}/cards', $request, '\SecurionPay\Response\Card');
    }

    /**
     * @param  string  $customerId
     * @param  string  $cardId
     * @return Card
     */
    public function retrieveCard($customerId, $cardId)
    {
        return $this->get("/customers/{$customerId}/cards/{$cardId}", '\SecurionPay\Response\Card');
    }

    /**
     * @param  CardUpdateRequest  $request
     * @return Card
     */
    public function updateCard($request)
    {
        return $this->post('/customers/{customerId}/cards/{cardId}', $request, '\SecurionPay\Response\Card');
    }

    /**
     * @param  string  $customerId
     * @param  string  $cardId
     * @return DeleteResponse
     */
    public function deleteCard($customerId, $cardId)
    {
        return $this->delete("/customers/{$customerId}/cards/{$cardId}", null, '\SecurionPay\Response\DeleteResponse');
    }

    /**
     * @param  CardListRequest  $request
     * @return ListResponse
     */
    public function listCards($request)
    {
        return $this->getList('/customers/{customerId}/cards', $request, '\SecurionPay\Response\Card');
    }

    /**
     * @param  string  $paymentMethodId
     * @return PaymentMethod
     */
    public function retrievePaymentMethod($paymentMethodId)
    {
        return $this->get("/payment-methods/{$paymentMethodId}", '\SecurionPay\Response\PaymentMethod');
    }

    /**
     * @param  string  $paymentMethodId
     * @return DeleteResponse
     */
    public function deletePaymentMethod($paymentMethodId)
    {
        return $this->delete("/payment-methods/{$paymentMethodId}", null, '\SecurionPay\Response\DeleteResponse');
    }

    /**
     * @param  PaymentMethodListRequest  $request
     * @return ListResponse
     */
    public function listPaymentMethods($request)
    {
        return $this->getList('/payment-methods', $request, '\SecurionPay\Response\PaymentMethod');
    }

    /**
     * @param  SubscriptionRequest  $request
     * @return Subscription
     */
    public function createSubscription($request)
    {
        return $this->post('/customers/{customerId}/subscriptions', $request, '\SecurionPay\Response\Subscription');
    }

    /**
     * @param  string  $customerId
     * @param  string  $subscriptionId
     * @return Subscription
     */
    public function retrieveSubscription($customerId, $subscriptionId)
    {
        return $this->get("/customers/{$customerId}/subscriptions/{$subscriptionId}", '\SecurionPay\Response\Subscription');
    }

    /**
     * @param  SubscriptionUpdateRequest  $request
     * @return Subscription
     */
    public function updateSubscription($request)
    {
        return $this->post('/customers/{customerId}/subscriptions/{subscriptionId}', $request, '\SecurionPay\Response\Subscription');
    }

    /**
     * @param  SubscriptionCancelRequest  $request
     * @return Subscription
     */
    public function cancelSubscription($request)
    {
        return $this->delete('/customers/{customerId}/subscriptions/{subscriptionId}', $request, '\SecurionPay\Response\Subscription');
    }

    /**
     * @param  SubscriptionListRequest  $request
     * @return ListResponse
     */
    public function listSubscriptions($request)
    {
        return $this->getList('/customers/{customerId}/subscriptions', $request, '\SecurionPay\Response\Subscription');
    }

    /**
     * @param  PlanRequest  $request
     * @return Plan
     */
    public function createPlan($request)
    {
        return $this->post('/plans', $request, '\SecurionPay\Response\Plan');
    }

    /**
     * @param  string  $planId
     * @return Plan
     */
    public function retrievePlan($planId)
    {
        return $this->get("/plans/{$planId}", '\SecurionPay\Response\Plan');
    }

    /**
     * @param  PlanUpdateRequest  $request
     * @return Plan
     */
    public function updatePlan($request)
    {
        return $this->post('/plans/{planId}', $request, '\SecurionPay\Response\Plan');
    }

    /**
     * @param  string  $planId
     * @return DeleteResponse
     */
    public function deletePlan($planId)
    {
        return $this->delete("/plans/{$planId}", null, '\SecurionPay\Response\DeleteResponse');
    }

    /**
     * @param  PlanListRequest  $request
     * @return ListResponse
     */
    public function listPlans($request = null)
    {
        return $this->getList('/plans', $request, '\SecurionPay\Response\Plan');
    }

    /**
     * @param  string  $eventId
     * @return Event
     */
    public function retrieveEvent($eventId)
    {
        return $this->get("/events/{$eventId}", '\SecurionPay\Response\Event');
    }

    /**
     * @param  EventListRequest  $request
     * @return ListResponse
     */
    public function listEvents($request = null)
    {
        return $this->getList('/events', $request, '\SecurionPay\Response\Event');
    }

    /**
     * @param  TokenRequest  $request
     * @return Token
     */
    public function createToken($request)
    {
        return $this->post('/tokens', $request, '\SecurionPay\Response\Token');
    }

    /**
     * @param  string  $tokenId
     * @return Token
     */
    public function retrieveToken($tokenId)
    {
        return $this->get("/tokens/{$tokenId}", '\SecurionPay\Response\Token');
    }

    /**
     * @param  BlacklistRuleRequest  $request
     * @return BlacklistRule
     */
    public function createBlacklistRule($request)
    {
        return $this->post('/blacklist', $request, '\SecurionPay\Response\BlacklistRule');
    }

    /**
     * @param  string  $blacklistRuleId
     * @return BlacklistRule
     */
    public function retrieveBlacklistRule($blacklistRuleId)
    {
        return $this->get("/blacklist/{$blacklistRuleId}", '\SecurionPay\Response\BlacklistRule');
    }

    /**
     * @param  string  $blacklistRuleId
     * @return DeleteResponse
     */
    public function deleteBlacklistRule($blacklistRuleId)
    {
        return $this->delete("/blacklist/{$blacklistRuleId}", null, '\SecurionPay\Response\DeleteResponse');
    }

    /**
     * @param  BlacklistRuleListRequest  $request
     * @return ListResponse
     */
    public function listBlacklistRules($request = null)
    {
        return $this->getList('/blacklist', $request, '\SecurionPay\Response\BlacklistRule');
    }

    /**
     * @param  CrossSaleOfferRequest  $request
     * @return CrossSaleOffer
     */
    public function createCrossSaleOffer($request)
    {
        return $this->post('/cross-sale-offers', $request, '\SecurionPay\Response\CrossSaleOffer');
    }

    /**
     * @param  string  $blacklistRuleId
     * @return CrossSaleOffer
     */
    public function retrieveCrossSaleOffer($crossSaleOfferId)
    {
        return $this->get("/cross-sale-offers/{$crossSaleOfferId}", '\SecurionPay\Response\CrossSaleOffer');
    }

    /**
     * @param  CrossSaleOfferUpdateRequest  $request
     * @return CrossSaleOffer
     */
    public function updateCrossSaleOffer($request)
    {
        return $this->post('/cross-sale-offers/{crossSaleOfferId}', $request, '\SecurionPay\Response\CrossSaleOffer');
    }

    /**
     * @param  string  $crossSaleOfferId
     * @return DeleteResponse
     */
    public function deleteCrossSaleOffer($crossSaleOfferId)
    {
        return $this->delete("/cross-sale-offers/{$crossSaleOfferId}", null, '\SecurionPay\Response\DeleteResponse');
    }

    /**
     * @param  CrossSaleOfferListRequest  $request
     * @return ListResponse
     */
    public function listCrossSaleOffers($request)
    {
        return $this->getList('/cross-sale-offers', $request, '\SecurionPay\Response\CrossSaleOffer');
    }

    /**
     * @param  CreditRequest  $request
     * @return Credit
     */
    public function createCredit($request)
    {
        return $this->post('/credits', $request, '\SecurionPay\Response\Credit');
    }

    /**
     * @param string creditId
     * @return Credit
     */
    public function retrieveCredit($creditId)
    {
        return $this->get("/credits/{$creditId}", '\SecurionPay\Response\Credit');
    }

    /**
     * @param  CreditUpdateRequest  $request
     * @return Credit
     */
    public function updateCredit($request)
    {
        return $this->post('/credits/{creditId}', $request, '\SecurionPay\Response\Credit');
    }

    /**
     * @param  CreditListRequest  $request
     * @return ListResponse
     */
    public function listCredits($request = null)
    {
        return $this->getList('/credits', $request, '\SecurionPay\Response\Credit');
    }

    /**
     * @param  string  $file
     * @param  string  $purpose
     * @return FileUpload
     */
    public function createFileUpload($file, $purpose)
    {
        $files = ['file' => $file];
        $form = ['purpose' => $purpose];

        return $this->multipart('/files', $files, $form, '\SecurionPay\Response\FileUpload');
    }

    /**
     * @param  string  $fileUploadId
     * @return FileUpload
     */
    public function retrieveFileUpload($fileUploadId)
    {
        return $this->getFromEndpoint($this->uploadsEndpoint, "/files/{$fileUploadId}", '\SecurionPay\Response\FileUpload');
    }

    /**
     * @param  FileUploadListRequest  $request
     * @return ListResponse
     */
    public function listFileUploads($request = null)
    {
        return $this->listFromEndpoint($this->uploadsEndpoint, '/files', $request, '\SecurionPay\Response\FileUpload');
    }

    /**
     * @param  string  $disputeId
     * @return Dispute
     */
    public function retrieveDispute($disputeId)
    {
        return $this->get("/disputes/{$disputeId}", '\SecurionPay\Response\Dispute');
    }

    /**
     * @param  DisputeUpdateRequest  $request
     * @return Dispute
     */
    public function updateDispute($request)
    {
        return $this->post('/disputes/{disputeId}', $request, '\SecurionPay\Response\Dispute');
    }

    /**
     * @param  string  $disputeId
     * @return Dispute
     */
    public function closeDispute($disputeId)
    {
        return $this->post("/disputes/{$disputeId}/close", null, '\SecurionPay\Response\Dispute');
    }

    /**
     * @param  DisputeListRequest  $request
     * @return ListResponse
     */
    public function listDisputes($request = null)
    {
        return $this->getList('/disputes', $request, '\SecurionPay\Response\Dispute');
    }

    /**
     * @param  string  $fraudWarningId
     * @return Dispute
     */
    public function retrieveFraudWarning($fraudWarningId)
    {
        return $this->get("/fraud-warnings/{$fraudWarningId}", '\SecurionPay\Response\FraudWarning');
    }

    /**
     * @param  FraudWarningListRequest  $request
     * @return ListResponse
     */
    public function listFraudWarnings($request = null)
    {
        return $this->getList('/fraud-warnings', $request, '\SecurionPay\Response\FraudWarning');
    }

    /**
     * @param  string  $refundId
     * @return Refund
     */
    public function retrieveRefund($refundId)
    {
        return $this->get("/refunds/{$refundId}", '\SecurionPay\Response\Refund');
    }

    /**
     * @param  RefundRequest  $request
     * @return Refund
     */
    public function createRefund($request)
    {
        return $this->post('/refunds', $request, '\SecurionPay\Response\Refund');
    }

    /**
     * @param  RefundListRequest  $request
     * @return ListResponse
     */
    public function listRefunds($request)
    {
        return $this->getList('/refunds', $request, '\SecurionPay\Response\Refund');
    }

    /**
     * @param  string  $payoutId
     * @return Payout
     */
    public function retrievePayout($payoutId)
    {
        return $this->get("/payouts/{$payoutId}", '\SecurionPay\Response\Payout');
    }

    /**
     * @return Payout
     */
    public function createPayout()
    {
        return $this->post('/payouts', null, '\SecurionPay\Response\Payout');
    }

    /**
     * @param  PayoutListRequest  $request
     * @return ListResponse
     */
    public function listPayouts($request = null)
    {
        return $this->getList('/payouts', $request, '\SecurionPay\Response\Payout');
    }

    /**
     * @param  PayoutTransactionListRequest  $request
     * @return ListResponse
     */
    public function listPayoutTransactions($request)
    {
        return $this->getList('/payout-transactions', $request, '\SecurionPay\Response\PayoutTransaction');
    }

    /**
     * @param  CheckoutRequest  $request
     * @return string
     */
    public function signCheckoutRequest($request)
    {
        $path = '';
        $data = $this->objectSerializer->serialize($request, $path);

        $signarute = hash_hmac('sha256', $data, $this->privateKey);

        return base64_encode($signarute.'|'.$data);
    }

    private function get($path, $responseClass)
    {
        return $this->getFromEndpoint($this->endpoint, $path, $responseClass);
    }

    private function getFromEndpoint($endpoint, $path, $responseClass)
    {
        $response = $this->connection->get($endpoint.$path, $this->buildHeaders());
        $this->ensureSuccess($response);

        return $this->objectSerializer->deserialize($response['body'], $responseClass);
    }

    private function post($path, $request, $responseClass)
    {
        $requestBody = $this->objectSerializer->serialize($request, $path);
        $response = $this->connection->post($this->endpoint.$path, $requestBody, $this->buildHeaders());
        $this->ensureSuccess($response);

        return $this->objectSerializer->deserialize($response['body'], $responseClass);
    }

    private function multipart($path, $files, $form, $responseClass)
    {
        $response = $this->connection->multipart($this->uploadsEndpoint.$path, $files, $form, $this->buildHeaders());
        $this->ensureSuccess($response);

        return $this->objectSerializer->deserialize($response['body'], $responseClass);
    }

    private function getList($path, $request, $elementClass)
    {
        return $this->listFromEndpoint($this->endpoint, $path, $request, $elementClass);
    }

    private function listFromEndpoint($endpoint, $path, $request, $elementClass)
    {
        $url = $this->buildQueryString($endpoint.$path, $request);
        $response = $this->connection->get($url, $this->buildHeaders());
        $this->ensureSuccess($response);

        return $this->objectSerializer->deserializeList($response['body'], $elementClass);
    }

    private function delete($path, $request, $responseClass)
    {
        $url = $this->endpoint.$this->buildQueryString($path, $request);
        $response = $this->connection->delete($url, $this->buildHeaders());
        $this->ensureSuccess($response);

        return $this->objectSerializer->deserialize($response['body'], $responseClass);
    }

    private function ensureSuccess($response)
    {
        if ($response['status'] != 200) {
            $error = $this->objectSerializer->deserialize($response['body'], '\SecurionPay\Response\ErrorResponse');
            throw new SecurionPayException($error);
        }

        return $response;
    }

    private function buildQueryString($path, $request)
    {
        if ($request == null) {
            return $path;
        }

        $queryString = $this->objectSerializer->serializeToQueryString($request, $path);

        return $path.$queryString;
    }

    private function buildHeaders()
    {
        return [
            'Authorization' => 'Basic '.base64_encode($this->privateKey.':'),
            'Content-Type' => 'application/json',
            'User-Agent' => ($this->userAgent ? $this->userAgent.' ' : '').'SecurionPay-PHP/'.self::VERSION.' (PHP/'.phpversion().')',
        ];
    }

    public function setPrivateKey($privateKey)
    {
        $this->privateKey = $privateKey;
    }

    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;
    }

    public function setUploadsEndpoint($uploadsEndpoint)
    {
        $this->uploadsEndpoint = $uploadsEndpoint;
    }

    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;
    }
}
