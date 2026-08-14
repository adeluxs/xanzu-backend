<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class PaymentGatewayException extends RuntimeException
{
    private ?string $transactionReference = null;

    public function __construct(
        string $message,
        private readonly string $provider,
        private readonly string $errorCode = 'PAYMENT_GATEWAY_ERROR',
        private readonly ?int $providerHttpStatus = null,
        private readonly ?string $providerCode = null,
        private readonly ?string $providerMessage = null,
        private readonly ?string $providerRequestId = null,
        private readonly ?string $endpoint = null,
        private readonly bool $retryable = false,
        private readonly array $diagnosticContext = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function attachTransactionReference(string $reference): self
    {
        $this->transactionReference = $reference;

        return $this;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function publicContext(): array
    {
        return array_filter([
            'provider' => $this->provider,
            'provider_code' => $this->providerCode,
            'provider_message' => $this->providerMessage,
            'provider_request_id' => $this->providerRequestId,
            'transaction_reference' => $this->transactionReference,
            'retryable' => $this->retryable,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    public function logContext(): array
    {
        return array_filter([
            ...$this->publicContext(),
            'provider_http_status' => $this->providerHttpStatus,
            'endpoint' => $this->endpoint,
            ...$this->diagnosticContext,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
