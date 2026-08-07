<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\TransferLimit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransferLimitController extends Controller
{
    public function index()
    {
        $limits = TransferLimit::orderBy('user_type')->get();
        return view('backend.transfer_limit.index', compact('limits'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_type' => ['required', 'in:buyer,merchant,all'],
            'min_amount' => ['required', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'daily_limit' => ['nullable', 'numeric', 'min:0'],
            'daily_transaction_count' => ['nullable', 'integer', 'min:0'],
            'monthly_limit' => ['nullable', 'numeric', 'min:0'],
            'monthly_transaction_count' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:0,1'],
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->filled('max_amount') && $request->max_amount > 0 && $request->max_amount < $request->min_amount) {
                $validator->errors()->add('max_amount', 'Maximum amount must be greater than minimum amount.');
            }
        });

        if ($validator->fails()) {
            notify()->error($validator->errors()->first());
            return back()->withErrors($validator)->withInput();
        }

        TransferLimit::updateOrCreate(
            ['user_type' => $request->user_type],
            $request->only([
                'min_amount',
                'max_amount',
                'daily_limit',
                'daily_transaction_count',
                'monthly_limit',
                'monthly_transaction_count',
                'status',
            ])
        );

        notify()->success('Transfer limit saved successfully.');
        return redirect()->route('admin.transfer-limit.index');
    }

    public function edit($id)
    {
        $limit = TransferLimit::findOrFail($id);
        $limits = TransferLimit::orderBy('user_type')->get();
        return view('backend.transfer_limit.index', compact('limits', 'limit'));
    }

    public function update(Request $request, $id)
    {
        $limit = TransferLimit::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'min_amount' => ['required', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'daily_limit' => ['nullable', 'numeric', 'min:0'],
            'daily_transaction_count' => ['nullable', 'integer', 'min:0'],
            'monthly_limit' => ['nullable', 'numeric', 'min:0'],
            'monthly_transaction_count' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:0,1'],
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->filled('max_amount') && $request->max_amount > 0 && $request->max_amount < $request->min_amount) {
                $validator->errors()->add('max_amount', 'Maximum amount must be greater than minimum amount.');
            }
        });

        if ($validator->fails()) {
            notify()->error($validator->errors()->first());
            return back()->withErrors($validator)->withInput();
        }

        $limit->update($request->only([
            'min_amount',
            'max_amount',
            'daily_limit',
            'daily_transaction_count',
            'monthly_limit',
            'monthly_transaction_count',
            'status',
        ]));

        notify()->success('Transfer limit updated successfully.');
        return redirect()->route('admin.transfer-limit.index');
    }

    public function destroy($id)
    {
        $limit = TransferLimit::findOrFail($id);
        $limit->delete();

        notify()->success('Transfer limit deleted successfully.');
        return redirect()->route('admin.transfer-limit.index');
    }
}
