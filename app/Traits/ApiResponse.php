<?php

namespace App\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\MessageBag;

trait ApiResponse
{
    public $pagination = 10;

    protected function successResponse($data = null, $message = 'Success', $code = 200, $meta = null)
    {
        return response()->json([
            'success' => true,
            'status' => true,
            'message' => $message,
            'code' => 'SUCCESS',
            'data' => $data,
            'meta' => $meta,
            'status_code' => $code,
            'request_id' => request()->attributes->get('request_id'),
        ], $code, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    protected function errorResponse(
        $message = 'Error',
        $code = 400,
        $data = null,
        $errors = null,
        ?string $errorCode = null
    )
    {
        return response()->json([
            'success' => false,
            'status' => false,
            'message' => $message,
            'code' => $errorCode ?? $this->errorCodeForStatus($code),
            'data' => $data,
            'errors' => $errors,
            'status_code' => $code,
            'request_id' => request()->attributes->get('request_id'),
        ], $code);
    }

    public function notFoundResponse($message = 'Resource Not Found')
    {
        return $this->errorResponse($message, 404);
    }

    public function validationErrorResponse($errors)
    {
        $structuredErrors = $errors instanceof MessageBag
            ? $errors->toArray()
            : $errors;
        $errorStr = is_string($structuredErrors)
            ? $structuredErrors
            : Arr::join(Arr::flatten($structuredErrors), ', ', ' and ');

        $errorStr = str($errorStr)->replace('.,', ',');

        return $this->errorResponse(
            $errorStr,
            422,
            null,
            is_string($structuredErrors) ? ['general' => [$structuredErrors]] : $structuredErrors,
            'VALIDATION_FAILED'
        );
    }

    public function unauthorizedResponse($message = 'Unauthorized')
    {
        return $this->errorResponse($message, 401);
    }

    public function successWithoutDataResponse($message = 'Success', $code = 200)
    {
        return response()->json([
            'success' => true,
            'status' => true,
            'message' => $message,
            'code' => 'SUCCESS',
            'data' => null,
            'status_code' => $code,
            'request_id' => request()->attributes->get('request_id'),
        ], $code);
    }

    private function errorCodeForStatus(int $status): string
    {
        return match ($status) {
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            409 => 'CONFLICT',
            422 => 'VALIDATION_FAILED',
            429 => 'RATE_LIMITED',
            default => $status >= 500 ? 'SERVER_ERROR' : 'REQUEST_FAILED',
        };
    }
}
