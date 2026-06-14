<?php

namespace App\Services;

use App\Enums\FinancialTransactionStatusEnum;
use App\Enums\FinancialTransactionTypeEnum;
use App\Models\FinancialTransaction;
use Illuminate\Support\Arr;
use InvalidArgumentException;

/**
 * Encapsulates the financial transaction business rules: creating
 * income/expense movements and transitioning their paid/open status.
 *
 * Amounts are always stored as a positive magnitude; the `type` carries the
 * income/expense direction, so this service guards that consistency rule.
 */
class FinancialTransactionService
{
    /**
     * Create a financial transaction.
     *
     * The amount must be strictly positive and the type a valid
     * income/expense value. New transactions always start as `open`.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): FinancialTransaction
    {
        $type = $this->resolveType($data['type'] ?? null);
        $amount = $this->assertPositiveAmount($data['amount'] ?? null);

        return FinancialTransaction::create([
            'player_id' => Arr::get($data, 'player_id'),
            'description' => $data['description'],
            'amount' => $amount,
            'type' => $type,
            'date' => $data['date'],
            'status' => FinancialTransactionStatusEnum::Open,
        ]);
    }

    /**
     * Transition a transaction to the paid status.
     */
    public function markAsPaid(FinancialTransaction $transaction): FinancialTransaction
    {
        $transaction->update(['status' => FinancialTransactionStatusEnum::Paid]);

        return $transaction;
    }

    /**
     * Transition a transaction back to the open status.
     */
    public function markAsOpen(FinancialTransaction $transaction): FinancialTransaction
    {
        $transaction->update(['status' => FinancialTransactionStatusEnum::Open]);

        return $transaction;
    }

    /**
     * Resolve and validate the transaction type.
     *
     * Accepts either an enum instance or its backing string value.
     */
    private function resolveType(mixed $type): FinancialTransactionTypeEnum
    {
        if ($type instanceof FinancialTransactionTypeEnum) {
            return $type;
        }

        $resolved = is_string($type) ? FinancialTransactionTypeEnum::tryFrom($type) : null;

        if ($resolved === null) {
            throw new InvalidArgumentException('The transaction type must be a valid income or expense value.');
        }

        return $resolved;
    }

    /**
     * Guard that the amount is numeric and strictly greater than zero.
     */
    private function assertPositiveAmount(mixed $amount): float
    {
        if (! is_numeric($amount) || (float) $amount <= 0) {
            throw new InvalidArgumentException('The transaction amount must be greater than zero.');
        }

        return (float) $amount;
    }
}
