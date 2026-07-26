<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Exception;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $coupons = Coupon::whereBelongsTo(auth()->user(), 'seller')
            ->when($request->filter, function ($query) use ($request) {
                match ($request->filter) {
                    'active' => $query->whereDate('expires_at', '>=', now()),
                    'inactive' => $query->whereDate('expires_at', '<', now())->orWhere('status', 0),
                    default => $query,
                };
            })
            ->paginate(15)->withQueryString();

        return view('frontend::coupons.index', compact('coupons'));
    }

    public function create()
    {
        return view('frontend::coupons.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'unique:coupons'],
            'discount_type' => ['required'],
            'discount_value' => ['required', 'numeric'],
            'expires_at' => ['required', 'date'],
            'max_use_limit' => ['required', 'numeric'],
            'status' => ['required'],
        ]);
        $validated['seller_id'] = auth()->id();
        $validated['total_used'] = 0;
        $validated['admin_approval'] = ! setting('coupon_approval', 'coupon');

        try {
            Coupon::create($validated);
            notify()->success(__('Coupon created successfully!'));
        } catch (Exception $e) {
            notify()->error(__('Coupon creation failed!'));
        }

        return to_buyerSellerRoute('coupon.index');
    }

    public function edit($id)
    {
        $coupon = Coupon::findOrFail(decrypt($id));

        return view('frontend::coupons.edit', compact('coupon'));
    }

    public function update(Request $request, $id)
    {
        $id = decrypt($id);
        $validated = $request->validate([
            'code' => 'required|unique:coupons,code,'.$id,
            'discount_type' => ['required'],
            'discount_value' => ['required', 'numeric'],
            'expires_at' => ['required', 'date'],
            'max_use_limit' => ['required', 'numeric'],
            'status' => ['required'],
        ]);

        $coupon = Coupon::findOrFail($id);

        try {
            $coupon->update($validated);
            notify()->success(__('Coupon updated successfully!'));
        } catch (Exception $e) {
            notify()->error(__('Coupon update failed!'));
        }

        return to_buyerSellerRoute('coupon.index');
    }

    public function destroy($id)
    {
        $coupon = Coupon::findOrFail(decrypt($id));
        $coupon->delete();
        notify()->success(__('Coupon deleted successfully!'));

        return to_buyerSellerRoute('coupon.index');
    }
}
