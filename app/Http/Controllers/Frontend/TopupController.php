<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Traits\Payment;
use Illuminate\Http\Request;

class TopupController extends Controller
{
    use Payment;

    public function __construct() {}

    public function index()
    {

        return view('frontend::topup.index');
    }

    public function purchase(Request $request)
    {

        $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'paymentMethod' => ['required:paymentMethod'],
        ]);

        $service = orderService();

        $orderData = [
            'items' => [],
            'is_topup' => true,
            'topup_amount' => $request->amount,
            'gateway_code' => $request->paymentMethod,
        ];

        try {
            $order = $service->create($orderData, $request);
        } catch (\Exception $e) {
            $service->dismissSession();
            notify()->error($e->getMessage());

            return back();
        }

        if (! $order) {
            $service->dismissSession();
            notify()->error(__('Count not create order!'));

            return back();
        }

        $order = $order->refresh();

        if (! $order) {
            $service->dismissSession();
            notify()->error(__('Count not create order!'));

            return back();
        }

        $order->transaction->order = $order;
        $order->transaction->listing = $order->listing;

        return $this->depositAutoGateway($request->paymentMethod, $order->transaction);
    }
}
