<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

class TransactionController extends Controller
{
    public function transactions(Request $request, $type = null)
    {
        $from_date = trim(@explode('to', request('daterange'))[0]);
        $to_date = trim(@explode('to', request('daterange'))[1]);
        $to_date = empty($to_date) ? $from_date : $to_date;

        $type = ! $type ? request('type') : $type;

        $transactions = Transaction::where('user_id', auth()->id())
            ->search(request('trx'))
            ->when(request('daterange'), function ($query) use ($from_date, $to_date) {
                $query->whereBetween('created_at', [
                    Date::parse($from_date)->startOfDay(),
                    Date::parse($to_date)->endOfDay(),
                ]);
            })
            ->when($type && $type !== 'all', function ($query) use ($type) {
                $query->type($type);
            })
            ->latest()
            ->paginate(request('limit', 15))
            ->withQueryString();

        return view('frontend::user.transaction.index', compact('transactions', 'type'));
    }
}
