<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WithdrawAccountResource;
use App\Models\WithdrawAccount;
use App\Services\WithdrawAccountService;
use App\Traits\ApiResponse;
use App\Traits\ImageUpload;
use Illuminate\Http\Request;

class WithdrawAccountController extends Controller
{
    use ApiResponse, ImageUpload;

    public function index(Request $request)
    {
        $user = $request->user();

        $withdrawAccounts = WithdrawAccount::query()
            ->where('user_id', $user->id)
            // ->when($request->keyword, fn($q) => $q->where('method_name', 'like', '%' . $request->keyword . '%'))
            ->latest()
            ->paginate($request->per_page ?? 10);

        return $this->successResponse(WithdrawAccountResource::collection($withdrawAccounts), __('Withdraw accounts retrieved successfully'));
    }

    public function store(Request $request)
    {
        $service = app(WithdrawAccountService::class);

        try {
            $service->validate($request->all(), null);

            $service->store($request->all(), null, $request->user()->id);
        } catch (\Throwable $th) {
            return $this->validationErrorResponse($th->getMessage());
        }

        return $this->successWithoutDataResponse(__('Withdraw account created successfully'));
    }

    public function show(Request $request, $id)
    {
        $account = WithdrawAccount::query()
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (!$account) {
            return $this->validationErrorResponse(__('Withdraw account not found'));
        }

        return $this->successResponse(new WithdrawAccountResource($account), __('Withdraw account retrieved successfully'));
    }

    public function update(Request $request, $id)
    {
        try {
            $service = app(WithdrawAccountService::class);
            $data = $request->all();
            $account = WithdrawAccount::query()
                ->where('user_id', $request->user()->id)
                ->find($id);

            if (!$account) {
                return $this->validationErrorResponse(__('Withdraw account not found'));
            }
            $method = $account->method;
            $service->validate($data, $method, true);

            $service->store($data, $method, $request->user()->id, $id);
        } catch (\Throwable $th) {
            return $this->validationErrorResponse($th->getMessage());
        }

        return $this->successWithoutDataResponse(__('Withdraw account updated successfully'));
    }

    public function destroy(Request $request, $id)
    {
        try {
            $service = app(WithdrawAccountService::class);
            $service->delete($id, $request->user()->id);

            return $this->successWithoutDataResponse(__('Withdraw account deleted successfully'));
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage() ?: __('Sorry! Something went wrong. Please try again'));
        }
    }
}
