<?php

namespace App\Services;

use App\Enums\FinancialTransactionStatusEnum;
use App\Enums\FinancialTransactionTypeEnum;
use App\Enums\PlayerStatusEnum;
use App\Models\FinancialTransaction;
use App\Models\Player;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
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
     * Update a transaction's attributes.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(FinancialTransaction $transaction, array $data): FinancialTransaction
    {
        return DB::transaction(function () use ($transaction, $data): FinancialTransaction {
            $transaction->update($data);

            return $transaction;
        });
    }

    /**
     * Generate the monthly payment charge for every active player in a month.
     *
     * Each charge is an open income transaction dated to the first day of the
     * month, with a stable description that makes the run idempotent: re-running
     * the same month never duplicates a player's charge. Returns the full set of
     * monthly-payment transactions for the month (newly created and existing).
     *
     * @return Collection<int, FinancialTransaction>
     */
    public function generateMonthlyPayments(string $month, string $amount): Collection
    {
        $date = CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth();
        $description = "Monthly payment {$month}";

        return DB::transaction(function () use ($date, $description, $amount): Collection {
            $activePlayerIds = Player::query()
                ->where('status', PlayerStatusEnum::Active)
                ->pluck('id');

            $chargedPlayerIds = FinancialTransaction::query()
                ->whereDate('date', $date)
                ->where('description', $description)
                ->whereNotNull('player_id')
                ->pluck('player_id');

            $missingPlayerIds = $activePlayerIds->diff($chargedPlayerIds);

            foreach ($missingPlayerIds as $playerId) {
                FinancialTransaction::create([
                    'player_id' => $playerId,
                    'description' => $description,
                    'amount' => $amount,
                    'type' => FinancialTransactionTypeEnum::Income,
                    'date' => $date,
                    'status' => FinancialTransactionStatusEnum::Open,
                ]);
            }

            return FinancialTransaction::query()
                ->select(['id', 'player_id', 'description', 'amount', 'type', 'date', 'status', 'created_at', 'updated_at'])
                ->whereDate('date', $date)
                ->where('description', $description)
                ->whereIn('player_id', $activePlayerIds)
                ->orderBy('player_id')
                ->get();
        });
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
