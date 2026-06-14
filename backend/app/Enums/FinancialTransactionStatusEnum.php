<?php

namespace App\Enums;

enum FinancialTransactionStatusEnum: string
{
    case Open = 'open';
    case Paid = 'paid';
}
