<?php

namespace App\Enums;

enum TxnType: string
{
    case Deposit = 'deposit';
    case Subtract = 'subtract';
    case ManualDeposit = 'manual_deposit';
    case Referral = 'referral';
    case SignupBonus = 'signup_bonus';
    case Withdraw = 'withdraw';
    case WithdrawAuto = 'withdraw_auto';
    case PlanPurchased = 'plan_purchased';
    case Refund = 'refund';
    case ProductOrder = 'product_order';
    case ProductSold = 'product_sold';
    case Topup = 'topup';
    case ProductOrderViaTopup = 'product_order_via_topup';

    case SellerFee = 'seller_fee';

    case OrderRefunded = 'order_refunded';
    case BnplInstallment = 'bnpl_installment';
    case Transfer = 'transfer';
}
