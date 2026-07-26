<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ScheduleTransferResource;
use App\Models\ScheduledTransfer;
use App\Services\ScheduledTransferService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ScheduledTransferController extends Controller
{
    use ApiResponse;

    public function store(Request $request)
    {
        $data = $request->all();
        $data['user_id'] = auth()->id();

        $transferService = app(ScheduledTransferService::class);

        try {
            if ($request->filled('step')) {
                $transferService->validate($data);
            } else {
                $scheduledTransfer = $transferService->create($data);
            }

            return $this->successResponse($scheduledTransfer ?? null, __('Scheduled transfer has been placed successfully.'));
        } catch (\Throwable $th) {
            return $this->validationErrorResponse($th->getMessage());
        }

    }

    public function delete(Request $request, $id)
    {
        $user = auth()->user();
        $scheduledTransfer = ScheduledTransfer::whereBelongsTo($user)->whereKey($id)->first();

        if (! $scheduledTransfer) {
            return $this->validationErrorResponse(__('Scheduled transfer not found.'));
        }

        $service = app(ScheduledTransferService::class);
        try {
            $service->deleteScheduledTransfer($scheduledTransfer);
        } catch (\Throwable $th) {
            return $this->validationErrorResponse($th->getMessage());
        }

        return $this->successWithoutDataResponse(__('Scheduled transfer has been deleted successfully.'));
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $scheduledTransfers = ScheduledTransfer::whereBelongsTo($user)->with([
            'user:id,first_name,last_name,email,avatar',
            'beneficiary:id,name',
            'fromCountry:id,name,currency_code',
            'toCountry:id,name,currency_code',
            'service:id,name,fields',
            'channel:id,name',
            'fundSource:id,name',
            'transferPurpose:id,name',
        ])->latest()->get();

        return $this->successResponse(ScheduleTransferResource::collection($scheduledTransfers), __('Scheduled transfers retrieved successfully.'));
    }

    public function toggleStatus(Request $request, $id)
    {
        $user = auth()->user();
        $scheduledTransfer = ScheduledTransfer::whereBelongsTo($user)->whereKey($id)->first();

        if (! $scheduledTransfer) {
            return $this->validationErrorResponse(__('Scheduled transfer not found.'));
        }

        $service = app(ScheduledTransferService::class);
        try {
            $service->toggleScheduledTransferStatus($scheduledTransfer);
        } catch (\Throwable $th) {
            return $this->validationErrorResponse($th->getMessage());
        }

        return $this->successWithoutDataResponse(__('Scheduled transfer status updated successfully.'));
    }
}
