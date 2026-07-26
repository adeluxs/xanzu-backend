<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\CreditLimitSplit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SplitController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage-credit-limit', ['only' => ['index']]);
        $this->middleware('permission:credit-limit-create', ['only' => ['store']]);
        $this->middleware('permission:credit-limit-edit', ['only' => ['update']]);
        $this->middleware('permission:credit-limit-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of all splits.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        $splits = CreditLimitSplit::latest()->get();

        return view('backend.split.index', compact('splits'));
    }

    /**
     * Store a newly created split.
     *
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'total_split' => ['nullable', 'regex:/^\d+(\.\d{1,2})?$/'],
            'payment_interval_amount' => ['required', 'integer', 'min:1'],
            'payment_interval_type' => ['required', 'in:day,week,month'],
            'interest_rate_amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'interest_rate_type' => ['required', 'in:percentage,fixed'],
            'delay_fine_amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'delay_fine_type' => ['required', 'in:percentage,fixed'],
            'status' => ['required'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back();
        }

        CreditLimitSplit::create([
            'total_split' => $request->float('total_split', 0),
            'payment_interval_amount' => $request->get('payment_interval_amount'),
            'payment_interval_type' => $request->get('payment_interval_type'),
            'interest_rate_amount' => $request->float('interest_rate_amount'),
            'interest_rate_type' => $request->get('interest_rate_type'),
            'delay_fine_amount' => $request->float('delay_fine_amount'),
            'delay_fine_type' => $request->get('delay_fine_type'),
            'status' => $request->boolean('status'),
        ]);

        notify()->success(__('Split added successfully'), 'Success');

        // clear cache
        cache()->forget('split_promo_message');

        return to_route('admin.split.index');
    }

    /**
     * Update the specified split.
     *
     * @return RedirectResponse
     */
    public function update(Request $request, CreditLimitSplit $split)
    {
        $validator = Validator::make($request->all(), [
            'total_split' => ['nullable', 'regex:/^\d+(\.\d{1,2})?$/'],
            'payment_interval_amount' => ['required', 'integer', 'min:1'],
            'payment_interval_type' => ['required', 'in:day,week,month'],
            'interest_rate_amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'interest_rate_type' => ['required', 'in:percentage,fixed'],
            'delay_fine_amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'delay_fine_type' => ['required', 'in:percentage,fixed'],
            'status' => ['required'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back();
        }

        $split->update([
            'total_split' => $request->float('total_split', 0),
            'payment_interval_amount' => $request->get('payment_interval_amount'),
            'payment_interval_type' => $request->get('payment_interval_type'),
            'interest_rate_amount' => $request->get('interest_rate_amount'),
            'interest_rate_type' => $request->get('interest_rate_type'),
            'delay_fine_amount' => $request->get('delay_fine_amount'),
            'delay_fine_type' => $request->get('delay_fine_type'),
            'status' => $request->boolean('status'),
        ]);

        notify()->success(__('Split updated successfully!'), 'Success');

        // clear cache        
        cache()->forget('split_promo_message');


        return to_route('admin.split.index');
    }

    /**
     * Remove the specified split.
     *
     * @return RedirectResponse
     */
    public function destroy(CreditLimitSplit $split)
    {
        $split->delete();

        notify()->success(__('Split deleted successfully!'), 'Success');

        return to_route('admin.split.index');
    }
}
