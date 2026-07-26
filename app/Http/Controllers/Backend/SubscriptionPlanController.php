<?php

namespace App\Http\Controllers\Backend;

use App\Enums\ReferralType;
use App\Http\Controllers\Controller;
use App\Models\LevelReferral;
use App\Models\SubscriptionPlan;
use App\Traits\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriptionPlanController extends Controller
{
    use ImageUpload;

    public function __construct()
    {
        $this->middleware('permission:plan-list', ['only' => ['index']]);
        $this->middleware('permission:plan-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:plan-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:plan-delete', ['only' => ['delete']]);
    }

    public function index()
    {
        $sortable = ['name', 'price', 'listing_limit', 'validity', 'is_featured', 'created_at'];

        $plans = SubscriptionPlan::when(request()->filled('sort_field') && in_array(request()->sort_field, $sortable), function ($query) {
            $query->orderBy(request()->sort_field, request()->sort_dir ?? 'asc');
        })
            ->paginate();

        return view('backend.subscription_plan.index', compact('plans'));
    }

    public function create()
    {
        $levels = LevelReferral::where('type', ReferralType::SubscriptionPlan->value)->orderBy('the_order')->get();

        return view('backend.subscription_plan.create', compact('levels'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'unique:subscription_plans'],
            'listing_limit' => ['required', 'numeric'],
            'price' => ['required', 'numeric'],
            'validity' => ['required', 'numeric'],
            'flash_sale_limit' => ['required', 'numeric', 'lte:listing_limit'],
            'referral_level' => ['required', 'numeric'],
            'badge' => ['required_if:featured,true'],
            'featured' => ['required', 'boolean'],
            'features' => ['nullable', 'array', 'min:1'],
            'features.*' => ['required', 'string'],
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'charge_type' => ['required'],
            'charge_value' => ['nullable', 'numeric'],

        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back()->withInput();
        }

        if (! $request->charge_value) {
            $request->merge(['charge_value' => 0]);
        }

        $image = $this->imageUploadTrait($request->file('image'));

        SubscriptionPlan::create([
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'listing_limit' => $request->get('listing_limit'),
            'price' => $request->get('price'),
            'validity' => $request->get('validity'),
            'flash_sale_limit' => $request->get('flash_sale_limit'),
            'referral_level' => $request->get('referral_level'),
            'badge' => $request->boolean('featured') ? $request->get('badge') : '',
            'is_featured' => $request->boolean('featured'),
            'features' => json_encode($request->get('features', [])),
            'image' => $image,
            'charge_type' => $request->get('charge_type'),
            'charge_value' => $request->get('charge_value'),
        ]);

        notify()->success(__('Subscription plan added successfully!'));

        return to_route('admin.subscription.plan.index');
    }

    public function edit($id)
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $levels = LevelReferral::where('type', ReferralType::SubscriptionPlan->value)->orderBy('the_order')->get();

        return view('backend.subscription_plan.edit', compact('plan', 'levels'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:subscription_plans,name,'.$id,
            'listing_limit' => ['required', 'numeric'],
            'price' => ['required', 'numeric'],
            'validity' => ['required', 'numeric'],
            'flash_sale_limit' => ['required', 'numeric', 'lte:listing_limit'],
            'referral_level' => ['required', 'numeric'],
            'badge' => ['required_if:featured,true'],
            'featured' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'charge_type' => ['required'],
            'charge_value' => ['nullable', 'numeric'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back()->withInput();
        }

        if (! $request->charge_value) {
            $request->merge(['charge_value' => 0]);
        }

        if ($request->hasFile('image')) {
            $image = $this->imageUploadTrait($request->file('image'), SubscriptionPlan::findOrFail($id)->image);
        } else {
            $image = SubscriptionPlan::findOrFail($id)->image;
        }

        SubscriptionPlan::findOrFail($id)->update([
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'listing_limit' => $request->get('listing_limit'),
            'price' => $request->get('price'),
            'validity' => $request->get('validity'),
            'flash_sale_limit' => $request->get('flash_sale_limit'),
            'referral_level' => $request->get('referral_level'),
            'badge' => $request->boolean('featured') ? $request->get('badge') : '',
            'is_featured' => $request->boolean('featured'),
            'features' => json_encode($request->get('features', [])),
            'image' => $image,
            'charge_type' => $request->get('charge_type'),
            'charge_value' => $request->get('charge_value'),
        ]);

        notify()->success(__('Subscription plan updated successfully!'));

        return to_route('admin.subscription.plan.index');
    }

    public function delete($id)
    {
        SubscriptionPlan::destroy($id);
        notify()->success(__('Subscription plan deleted successfully!'));

        return to_route('admin.subscription.plan.index');
    }
}
