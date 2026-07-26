<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\CreditLimit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CreditLimitController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage-credit-limit', ['only' => ['index']]);
        $this->middleware('permission:credit-limit-create', ['only' => ['store']]);
        $this->middleware('permission:credit-limit-edit', ['only' => ['update']]);
        $this->middleware('permission:credit-limit-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        $creditLimits = CreditLimit::get();

        return view('backend.credit-limit.index', compact('creditLimits'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'level' => ['required', 'unique:credit_limits,level'],
            'minimum_transactions' => ['required_if:is_kyc,0', 'integer', 'min:0'],
            'credit_amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'status' => ['required'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back();
        }

        CreditLimit::create([
            'level' => $request->get('level'),
            'minimum_transactions' => $request->get('minimum_transactions'),
            'is_kyc' => $request->boolean('is_kyc'),
            'credit_amount' => $request->get('credit_amount'),
            'status' => $request->boolean('status'),
        ]);

        notify()->success(__('Credit Limit created successfully'), 'Success');

        return to_route('admin.credit-limit.index');
    }

    /**
     * Update the specified resource in storage.
     *
     * @return RedirectResponse
     */
    public function update(Request $request, CreditLimit $creditLimit)
    {
        $validator = Validator::make($request->all(), [
            'level' => 'required|unique:credit_limits,level,'.$creditLimit->id,
            'minimum_transactions' => ['required_if:is_kyc,0', 'integer', 'min:0'],
            'credit_amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'status' => ['required'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back();
        }

        $data = [
            'level' => $request->get('level'),
            'minimum_transactions' => $request->float('minimum_transactions'),
            'is_kyc' => $request->boolean('is_kyc'),
            'credit_amount' => $request->get('credit_amount'),
            'status' => $request->boolean('status'),
        ];

        $creditLimit->update($data);

        notify()->success(__('Credit Limit updated successfully!'), 'Success');

        return to_route('admin.credit-limit.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return RedirectResponse
     */
    public function destroy(CreditLimit $creditLimit)
    {
        $creditLimit->splits()->delete();
        $creditLimit->delete();

        notify()->success(__('Credit Limit deleted successfully!'), 'Success');

        return to_route('admin.credit-limit.index');
    }
}
