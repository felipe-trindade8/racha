<?php

namespace App\Services;

use App\Enums\FinancialTransactionStatusEnum;
use App\Enums\FinancialTransactionTypeEnum;
use App\Enums\PlayerStatusEnum;
use App\Models\FinancialTransaction;
use App\Models\Player;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
     * The columns selected when listing transactions.
     *
     * @var list<string>
     */
    private const DEFAULT_COLUMNS = ['id', 'player_id', 'description', 'amount', 'type', 'date', 'status', 'created_at', 'updated_at'];

    /**
     * Return a paginated list of transactions, newest first.
     *
     * Each filter is optional and only narrows the result when provided: `type`
     * and `status` match exactly, `playerId` matches the owning player, and
     * `month` (a `Y-m` string) restricts the list to that calendar month.
     *
     * @param  list<string>  $columns
     * @return LengthAwarePaginator<int, FinancialTransaction>
     */
    public function paginate(
        int $perPage = 15,
        ?FinancialTransactionTypeEnum $type = null,
        ?FinancialTransactionStatusEnum $status = null,
        ?string $month = null,
        ?int $playerId = null,
        array $columns = self::DEFAULT_COLUMNS,
    ): LengthAwarePaginator {
        return FinancialTransaction::query()
            ->when($type !== null, fn ($query) => $query->where('type', $type))
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when($playerId !== null, fn ($query) => $query->where('player_id', $playerId))
            ->when($month !== null && $month !== '', function ($query) use ($month): void {
                $start = CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth();

                $query->whereBetween('date', [$start, $start->endOfMonth()]);
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($perPage, $columns);
    }

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
     * Generate the monthly payment charge for every active player.
     *
     * Each charge is an open income transaction dated to the provided date. The
     * run is idempotent per player and month: the stable, month-derived
     * description makes re-running for any day of the same month never duplicate
     * a player's charge. Returns the full set of that month's monthly-payment
     * transactions (newly created and existing).
     *
     * @return Collection<int, FinancialTransaction>
     */
    public function generateMonthlyPayments(string $date, string $amount): Collection
    {
        $date = CarbonImmutable::createFromFormat('Y-m-d', $date);
        $description = "Monthly payment {$date->format('Y-m')}";

        return DB::transaction(function () use ($date, $description, $amount): Collection {
            $activePlayerIds = Player::query()
                ->where('status', PlayerStatusEnum::Active)
                ->pluck('id');

            $chargedPlayerIds = FinancialTransaction::query()
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
