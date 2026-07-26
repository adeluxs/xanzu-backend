<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShippingAddress;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShippingAddressController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $addresses = ShippingAddress::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return $this->successResponse($addresses, __('Shipping addresses retrieved successfully'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => ['required', 'in:home,office'],
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $data = $validator->validated();
        $data['user_id'] = $request->user()->id;
        $data['is_default'] = (bool) ($data['is_default'] ?? false);

        if ($data['is_default']) {
            ShippingAddress::query()
                ->where('user_id', $request->user()->id)
                ->update(['is_default' => false]);
        }

        // First address should become default automatically.
        if (!$data['is_default']) {
            $hasAnyAddress = ShippingAddress::query()->where('user_id', $request->user()->id)->exists();
            if (!$hasAnyAddress) {
                $data['is_default'] = true;
            }
        }

        $address = ShippingAddress::query()->create($data);

        return $this->successResponse($address, __('Shipping address created successfully'));
    }

    public function show(Request $request, $id)
    {
        $address = ShippingAddress::query()
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (!$address) {
            return $this->notFoundResponse(__('Shipping address not found'));
        }

        return $this->successResponse($address, __('Shipping address retrieved successfully'));
    }

    public function update(Request $request, $id)
    {
        $address = ShippingAddress::query()
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (!$address) {
            return $this->notFoundResponse(__('Shipping address not found'));
        }

        $validator = Validator::make($request->all(), [
            'type' => ['sometimes', 'in:home,office'],
            'full_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'address' => ['sometimes', 'string'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $data = $validator->validated();

        if (array_key_exists('is_default', $data) && $data['is_default']) {
            ShippingAddress::query()
                ->where('user_id', $request->user()->id)
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        $address->update($data);

        // Ensure there is always one default address.
        $hasDefault = ShippingAddress::query()
            ->where('user_id', $request->user()->id)
            ->where('is_default', true)
            ->exists();

        if (!$hasDefault) {
            $address->update(['is_default' => true]);
        }

        return $this->successResponse($address->fresh(), __('Shipping address updated successfully'));
    }

    public function destroy(Request $request, $id)
    {
        $address = ShippingAddress::query()
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (!$address) {
            return $this->notFoundResponse(__('Shipping address not found'));
        }

        $wasDefault = (bool) $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $nextAddress = ShippingAddress::query()
                ->where('user_id', $request->user()->id)
                ->latest('id')
                ->first();

            if ($nextAddress) {
                $nextAddress->update(['is_default' => true]);
            }
        }

        return $this->successWithoutDataResponse(__('Shipping address deleted successfully'));
    }

    public function setDefault(Request $request, $id)
    {
        $address = ShippingAddress::query()
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (!$address) {
            return $this->notFoundResponse(__('Shipping address not found'));
        }

        ShippingAddress::query()
            ->where('user_id', $request->user()->id)
            ->update(['is_default' => false]);

        $address->update(['is_default' => true]);

        return $this->successResponse($address->fresh(), __('Default shipping address updated successfully'));
    }
}
