<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DepositMethod;
use App\Services\AddMoneyService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class AddMoneyController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AddMoneyService $addMoneyService
    ) {
    }

    public function index(Request $request)
    {


        $methods = DepositMethod::when($request->has('currency'), function ($query) use ($request) {
            $query->where('currency', $request->currency);
        })->where('status', 1)->when($request->has('auto'), function ($q) {
            $q->auto();
        })->get()->map(function ($method, $key) {
            $method->logo = asset($method->logo ?? $method->gateway->logo);
            $method->field_options = dynamicFieldKeyFormat($method->field_options ?? []);

            return $method;
        });

        return $this->successResponse($methods);
    }

    public function store(Request $request)
    {

        try {
            $this->addMoneyService->validate($request->all());
            $response = $this->addMoneyService->process($request->amount, $request->gateway, $request->customFields ?? null);

            return $this->successResponse($response, __('Deposit request successful.'));
        } catch (\Exception $e) {
            return $this->validationErrorResponse($e->getMessage());
        }
    }
}
