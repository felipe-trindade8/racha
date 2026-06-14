<?php

namespace App\Enums;

enum FinancialTransactionTypeEnum: string
{
    case Income = 'income';
    case Expense = 'expense';
}
