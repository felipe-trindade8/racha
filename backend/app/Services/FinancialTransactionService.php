<?php

namespace App\Services;

use App\Enums\FinancialTransactionStatusEnum;
use App\Models\FinancialTransaction;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Encapsulates the financial transaction business rules: creating
 * income/expense movements and transitioning their paid/open status.
 *
 * Input validation (amount, type) lives in the Form Requests; this service
 * trusts the data it receives and owns the persistence rules only. Amounts are
 * stored as a positive magnitude with the `type` carrying the income/expense
 * direction, and new transactions always start as `open`.
 */
class FinancialTransactionService
{
    /**
     * Create a financial transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): FinancialTransaction
    {
        return DB::transaction(fn (): FinancialTransaction => FinancialTransaction::create([
            'player_id' => Arr::get($data, 'player_id'),
            'description' => $data['description'],
            'amount' => $data['amount'],
            'type' => $data['type'],
            'date' => $data['date'],
            'status' => FinancialTransactionStatusEnum::Open,
        ]));
    }

    /**
     * Transition a transaction to a new status (open or paid).
     */
    public function updateStatus(FinancialTransaction $transaction, FinancialTransactionStatusEnum $status): FinancialTransaction
    {
        return DB::transaction(function () use ($transaction, $status): FinancialTransaction {
            $transaction->update(['status' => $status]);

            return $transaction;
        });
    }
}
