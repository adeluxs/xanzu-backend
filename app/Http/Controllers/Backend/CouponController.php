<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $sortable = ['discount_value', 'expires_at', 'max_use_limit', 'total_used', 'created_at'];

        $coupons = Coupon::when($request->filled('status'), function ($query) use ($request) {
            if (is_numeric($request->status)) {
                $query->where('status', $request->status);
            }
        })
            ->when($request->filled('sort_field') && in_array($request->sort_field, $sortable), function ($query) use ($request) {
                $query->orderBy($request->sort_field, $request->sort_dir ?? 'asc');
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('code', 'like', '%'.$request->search.'%');
            })
            ->when($request->filled('perPage'), function ($query) use ($request) {
                $query->paginate($request->perPage);
            })
            ->latest()->paginate($request->perPage ?? 15);

        return view('backend.coupons.index', compact('coupons'));
    }

    public function create()
    {
        return view('backend.coupons.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => ['required', 'unique:coupons'],
            'discount_type' => ['required'],
            'discount_value' => ['required', 'numeric'],
            'expires_at' => ['required', 'date'],
            'is_home' => ['nullable', 'boolean'],
        ]);

        try {
            $data = $request->only([
                'code',
                'discount_type',
                'discount_value',
                'expires_at',
                'max_use_limit',
                'total_used',
                'status',
                'is_home',
            ]);

            DB::transaction(function () use ($request, $data) {
                if ($request->boolean('is_home')) {
                    Coupon::query()->whereNotNull('is_home')->update(['is_home' => null]);
                    $data['is_home'] = 1;
                } else {
                    $data['is_home'] = null;
                }

                Coupon::create($data);
            });

            notify()->success(__('Coupon created successfully!'));
        } catch (Exception $e) {
            notify()->error(__('Coupon creation failed!'));
        }

        return to_route('admin.coupon.index');
    }

    public function edit($id)
    {
        $coupon = Coupon::findOrFail($id);

        return view('backend.coupons.edit', compact('coupon'));
    }

    public function update(Request $request, $id)
    {
        $coupon = Coupon::findOrFail($id);

        $request->validate([
            'code' => ['required', 'unique:coupons,code,'.$coupon->id],
            'discount_type' => ['required'],
            'discount_value' => ['required', 'numeric'],
            'expires_at' => ['required', 'date'],
            'is_home' => ['nullable', 'boolean'],
        ]);

        try {
            // Only update the allowed fields so we don't try to set columns that
            // don't exist (for example: seller_id when the column is absent).
            $data = $request->only([
                'code',
                'discount_type',
                'discount_value',
                'expires_at',
                'max_use_limit',
                'total_used',
                'status',
                'is_home',
            ]);

            DB::transaction(function () use ($request, $coupon, $data) {
                if ($request->boolean('is_home')) {
                    Coupon::query()
                        ->where('id', '!=', $coupon->id)
                        ->whereNotNull('is_home')
                        ->update(['is_home' => null]);
                    $data['is_home'] = 1;
                } else {
                    $data['is_home'] = null;
                }

                $coupon->update($data);
            });

            notify()->success(__('Coupon updated successfully!'));
        } catch (Exception $e) {
            // dd($e);
            notify()->error(__('Coupon update failed!'));
        }

        return to_route('admin.coupon.index');
    }

    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();
        notify()->success(__('Coupon deleted successfully!'));

        return to_route('admin.coupon.index');
    }
}
