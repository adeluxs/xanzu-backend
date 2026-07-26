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
            'status' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
            'status_code' => $code,
        ], $code, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    protected function errorResponse($message = 'Error', $code = 400, $data = null)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'data' => $data,
            'status_code' => $code,
        ], $code);
    }

    public function notFoundResponse($message = 'Resource Not Found')
    {
        return $this->errorResponse($message, 404);
    }

    public function validationErrorResponse($errors)
    {
        $errors = $errors instanceof MessageBag ? $errors->all() : $errors;
        $errorStr = is_string($errors) ? $errors : \Arr::join(Arr::flatten($errors), ', ', ' and ');

        $errorStr = str($errorStr)->replace('.,', ',');

        return $this->errorResponse($errorStr, 422, null);
    }

    public function unauthorizedResponse($message = 'Unauthorized')
    {
        return $this->errorResponse($message, 401);
    }

    public function successWithoutDataResponse($message = 'Success', $code = 200)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'status_code' => $code,
        ], $code);
    }
}
