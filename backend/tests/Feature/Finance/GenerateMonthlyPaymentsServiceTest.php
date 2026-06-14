<?php

use App\Enums\FinancialTransactionStatusEnum;
use App\Enums\FinancialTransactionTypeEnum;
use App\Models\FinancialTransaction;
use App\Models\Player;
use App\Services\FinancialTransactionService;

beforeEach(function (): void {
    $this->service = app(FinancialTransactionService::class);
});

it('generates one open income charge per active player dated to the given date', function (): void {
    $active = Player::factory()->count(3)->create();

    $transactions = $this->service->generateMonthlyPayments('2026-06-14', '50.00');

    expect($transactions)->toHaveCount(3);

    foreach ($active as $player) {
        $charge = FinancialTransaction::where('player_id', $player->id)->sole();

        expect($charge->type)->toBe(FinancialTransactionTypeEnum::Income)
            ->and($charge->status)->toBe(FinancialTransactionStatusEnum::Open)
            ->and($charge->amount)->toBe('50.00')
            ->and($charge->date->toDateString())->toBe('2026-06-14')
            ->and($charge->description)->toBe('Monthly payment 2026-06');
    }
});

it('skips inactive and injured players', function (): void {
    $active = Player::factory()->create();
    Player::factory()->inactive()->create();
    Player::factory()->injured()->create();

    $transactions = $this->service->generateMonthlyPayments('2026-06-14', '50.00');

    expect($transactions)->toHaveCount(1)
        ->and($transactions->first()->player_id)->toBe($active->id);
});

it('does not duplicate charges when re-run for the same month', function (): void {
    Player::factory()->count(2)->create();

    $this->service->generateMonthlyPayments('2026-06-14', '50.00');
    $this->service->generateMonthlyPayments('2026-06-14', '50.00');

    expect(FinancialTransaction::count())->toBe(2);
});

it('does not duplicate charges when re-run for a different day of the same month', function (): void {
    Player::factory()->count(2)->create();

    $this->service->generateMonthlyPayments('2026-06-05', '50.00');
    $this->service->generateMonthlyPayments('2026-06-20', '50.00');

    expect(FinancialTransaction::count())->toBe(2);
});

it('charges a newly added active player on a later run without duplicating others', function (): void {
    Player::factory()->count(2)->create();
    $this->service->generateMonthlyPayments('2026-06-14', '50.00');

    $late = Player::factory()->create();
    $transactions = $this->service->generateMonthlyPayments('2026-06-14', '50.00');

    expect(FinancialTransaction::count())->toBe(3)
        ->and($transactions)->toHaveCount(3)
        ->and(FinancialTransaction::where('player_id', $late->id)->count())->toBe(1);
});

it('charges separately for different months', function (): void {
    Player::factory()->create();

    $this->service->generateMonthlyPayments('2026-06-14', '50.00');
    $this->service->generateMonthlyPayments('2026-07-14', '50.00');

    expect(FinancialTransaction::count())->toBe(2);
});
