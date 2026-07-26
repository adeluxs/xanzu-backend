<?php

namespace App\Services;

use App\Models\Order;

class OrderNumberService
{
    public function generateNextNumber(): int
    {
        $lastOrderNumber = Order::lockForUpdate()->max('order_number') ?? 1000;

        return max(1001, $lastOrderNumber + 1);
    }
}
