<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Resources\KYCResource;
use App\Models\UserKyc;
use App\Services\KycService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class KycController extends Controller
{
    use ApiResponse;


    public function histories(Request $request)
    {
        $service = app()->make(KycService::class);
        $userKycs = $service->submittedKyc($request->user());

        foreach ($userKycs as $kyc) {
            $kycData = [];
            foreach ($kyc->data as $key => $data) {
                $kycData[$key] = pathinfo($data, PATHINFO_EXTENSION) && file_exists(base_path('assets/' . $data)) ? asset($data) : $data;
            }
            $kyc->data = $kycData;
            $kyc->icon = optional($kyc->kyc)->icon ? asset(optional($kyc->kyc)->icon) : null;
            $kyc->created_at = optional($kyc->created_at)->format('d M Y h:i A');
            $kyc->updated_at = optional($kyc->updated_at)->format('d M Y h:i A');
            $kyc->orgKyc = new KYCResource($kyc->kyc);
            unset($kyc->kyc);
        }

        return $this->successResponse(data: $userKycs, meta: [
            'last_rejection_reason' => UserKyc::where('user_id', $request->user()->id)->where('status', 'rejected')->latest()->value('message')
        ]);
    }
}