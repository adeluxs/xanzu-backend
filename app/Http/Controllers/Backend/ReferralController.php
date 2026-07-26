<?php

namespace App\Http\Controllers\Backend;

use App\Enums\ReferralType;
use App\Models\LevelReferral;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;

class ReferralController implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('permission:manage-referral', ['only' => ['index']]),
            new Middleware('permission:referral-create', ['only' => ['store']]),
            new Middleware('permission:referral-edit', ['only' => ['update']]),
            new Middleware('permission:referral-delete', ['only' => ['destroy']]),
        ];
    }

    public function index()
    {
        $referralType = ReferralType::cases();

        $deposits = LevelReferral::where('type', ReferralType::Topup->value)->get();
        $productOrders = LevelReferral::where('type', ReferralType::ProductOrder->value)->get();

        $data = [
            'topup' => $deposits,
            'product_order' => $productOrders,
        ];

        return view('backend.referral.index', ['referralType' => $referralType, 'data' => $data]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'level_type' => new Enum(ReferralType::class),
            'bounty' => 'required',
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first());

            return back();
        }

        try {
            $data = [
                'type' => $request->level_type,
                'bounty' => $request->bounty,
            ];

            $position = LevelReferral::where('type', $request->level_type)->max('the_order');
            $data = array_merge($data, ['the_order' => $position + 1]);

            LevelReferral::create($data);

            $status = 'success';
            $message = __('Referral level created successfully!');

            notify()->$status($message, $status);

            return redirect()->route('admin.referral.index');
        } catch (\Exception $exception) {
            $status = 'warning';
            $message = __('something is wrong: ').$exception->getMessage();

            notify()->$status($message, $status);

            return back();
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'bounty' => 'required',
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first());

            return back();
        }

        try {
            $data = [
                'bounty' => $request->bounty,
            ];

            LevelReferral::find($id)->update($data);

            $status = 'success';
            $message = __('Referral level updated successfully');

            notify()->$status($message, $status);

            return redirect()->route('admin.referral.index');
        } catch (\Exception $exception) {
            $status = 'warning';
            $message = __('something is wrong: ').$exception->getMessage();

            notify()->$status($message, $status);

            return back();
        }
    }

    public function statusUpdate(Request $request)
    {
        try {
            $key = $request->type;
            try {
                $value = setting($key, 'referral_level') ? 0 : 1;
            } catch (\Throwable $th) {
                $value = 0;
            }
            Setting::add($key, $value, 'referral_level');

            $status = 'success';
            $message = ucwords(str_replace('_', ' ', $key)).' '.__('Status updated successfully!');
        } catch (\Exception $exception) {
            $status = 'error';
            $message = __('Sorry, something went wrong!');
        }

        notify()->$status($message, $status);

        return back();
    }

    public function destroy(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $levelReferral = LevelReferral::find($id);
            $levelReferral->delete();

            $reorders = LevelReferral::where('type', $request->type)->get();
            $i = 1;
            foreach ($reorders as $reorder) {
                $reorder->the_order = $i;
                $reorder->save();
                $i++;
            }

            DB::commit();

            $status = 'success';
            $message = __('Referral level deleted successfully!');

            notify()->$status($message, $status);

            return redirect()->route('admin.referral.index');
        } catch (\Exception $exception) {
            DB::rollBack();

            $status = 'warning';
            $message = __('something is wrong: ').$exception->getMessage();

            notify()->$status($message, $status);

            return back();
        }
    }

    public function settings()
    {
        $setting = Setting::where('name', 'referral_rules')->first();
        $rules = json_decode($setting?->val);

        return view('backend.referral.settings', ['rules' => $rules]);
    }
}
