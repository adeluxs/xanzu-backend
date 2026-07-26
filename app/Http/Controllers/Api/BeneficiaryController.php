<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BeneficiaryResource;
use App\Services\BeneficiaryService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class BeneficiaryController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $beneficiaries = auth()->user()->beneficiary;

        return $this->successResponse(BeneficiaryResource::collection($beneficiaries), __('Beneficiaries retrieved successfully'));
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $beneficiaryService = app(BeneficiaryService::class);

        try {
            $beneficiaryService->validate($data);
            $beneficiary = $beneficiaryService->store($data);
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage(), 422);
        }

        return $this->successResponse($beneficiary, __('Beneficiary added successfully'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();
        $beneficiaryService = app(BeneficiaryService::class);
        $beneficiary = auth()->user()->beneficiary()->where('id', $id)->first();

        if (! $beneficiary) {
            return $this->notFoundResponse(__('Beneficiary not found'));
        }

        try {
            $beneficiaryService->validate($data, true);
            $beneficiary = $beneficiaryService->update($data, $beneficiary);
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage(), 422);
        }

        return $this->successWithoutDataResponse(__('Beneficiary updated successfully'));
    }

    public function destroy($id)
    {
        $beneficiary = auth()->user()->beneficiary()->where('id', $id)->first();

        if (! $beneficiary) {
            return $this->notFoundResponse(__('Beneficiary not found'));
        }

        try {
            app(BeneficiaryService::class)->delete($beneficiary);

        } catch (\Throwable $th) {
            return $this->errorResponse(__('Failed to delete beneficiary. Please try again.'), 422);
        }

        return $this->successWithoutDataResponse(__('Beneficiary deleted successfully'));
    }
}
