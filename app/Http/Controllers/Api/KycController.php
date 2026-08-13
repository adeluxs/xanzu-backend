<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\KYCResource;
use App\Models\Kyc;
use App\Services\KycService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class KycController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $service = app()->make(KycService::class);
        $kycs = $service->submittableKyc($request->user());
        $kycs = KYCResource::collection($kycs);

        return $this->successResponse($kycs);
    }

    public function histories(Request $request)
    {
        $service = app()->make(KycService::class);
        $userKycs = $service->submittedKyc($request->user());
        foreach ($userKycs as $key => $kyc) {
            $kycData = [];
            foreach ($kyc->data as $key => $data) {
                $kycData[$key] = pathinfo($data, PATHINFO_EXTENSION) && file_exists(base_path('assets/' . $data)) ? asset($data) : $data;
            }
            $kyc->data = $kycData;
            $kyc->icon = $kyc->kyc->icon ? asset($kyc->kyc->icon) : null;
            $kyc->created_at = optional($kyc->created_at)->format('d M Y h:i A');
            $kyc->updated_at = optional($kyc->updated_at)->format('d M Y h:i A');
            unset($kyc->kyc);
        }

        return $this->successResponse($userKycs);
    }

    public function store(Request $request)
    {
        return $this->submitKyc($request);
    }

    public function resubmit(Request $request)
    {
        return $this->submitKyc($request);
    }

    private function submitKyc(Request $request)
    {
        $kyc = Kyc::find($request->kyc_id);
        if (!$kyc) {
            return $this->notFoundResponse(__('KYC not found.'));
        }

        $service = app()->make(KycService::class);

        try {
            $service->verify($kyc, $request->fields ?? []);
        } catch (ValidationException $th) {
            return $this->validationErrorResponse($th->errors());
        }

        try {
            $service->submitKyc($request->fields ?? [], $kyc);

            return $this->successWithoutDataResponse(__('KYC submission has been submitted successfully. We will review and get back to you soon.'));
        } catch (\Exception $e) {
            report($e);

            return $this->errorResponse(
                __('Unable to submit KYC. Please try again.'),
                500
            );
        }
    }
}
