<?php

use App\Enums\FinancialTransactionStatusEnum;
use App\Enums\FinancialTransactionTypeEnum;
use App\Models\FinancialTransaction;
use App\Models\Player;
use Illuminate\Support\Carbon;

it('persists a financial transaction', function (): void {
    $transaction = FinancialTransaction::factory()->create();

    expect($transaction->exists)->toBeTrue()
        ->and(FinancialTransaction::query()->count())->toBe(1);
});

it('casts the type and status to their enums', function (): void {
    $transaction = FinancialTransaction::factory()->create();

    expect($transaction->type)->toBeInstanceOf(FinancialTransactionTypeEnum::class)
        ->and($transaction->status)->toBeInstanceOf(FinancialTransactionStatusEnum::class);
});

it('casts the date to a Carbon instance', function (): void {
    $transaction = FinancialTransaction::factory()->create(['date' => '2026-06-14']);

    expect($transaction->date)->toBeInstanceOf(Carbon::class)
        ->and($transaction->date->toDateString())->toBe('2026-06-14');
});

it('casts the amount to a two-decimal string', function (): void {
    $transaction = FinancialTransaction::factory()->create(['amount' => 12.5]);

    expect($transaction->amount)->toBe('12.50');
});

it('defaults the status to open at the database level when none is provided', function (): void {
    $transaction = FinancialTransaction::create([
        'description' => 'Field rental',
        'amount' => 200,
        'type' => FinancialTransactionTypeEnum::Expense,
        'date' => '2026-06-14',
    ]);

    expect($transaction->fresh()->status)->toBe(FinancialTransactionStatusEnum::Open);
});

it('allows a null player for group-wide transactions', function (): void {
    $transaction = FinancialTransaction::factory()->create(['player_id' => null]);

    expect($transaction->player_id)->toBeNull()
        ->and($transaction->player)->toBeNull();
});

it('belongs to a player when one is associated', function (): void {
    $player = Player::factory()->create();

    $transaction = FinancialTransaction::factory()->forPlayer($player)->create();

    expect($transaction->player)->toBeInstanceOf(Player::class)
        ->and($transaction->player->id)->toBe($player->id);
});

it('nulls the player_id when the associated player is deleted', function (): void {
    $player = Player::factory()->create();
    $transaction = FinancialTransaction::factory()->forPlayer($player)->create();

    $player->delete();

    expect($transaction->fresh()->player_id)->toBeNull();
});

it('exposes a players financial transactions through the relationship', function (): void {
    $player = Player::factory()->create();
    FinancialTransaction::factory()->forPlayer($player)->count(2)->create();
    FinancialTransaction::factory()->create();

    expect($player->financialTransactions)->toHaveCount(2);
});

it('creates an income transaction through the factory state', function (): void {
    $transaction = FinancialTransaction::factory()->income()->create();

    expect($transaction->type)->toBe(FinancialTransactionTypeEnum::Income);
});

it('creates an expense transaction through the factory state', function (): void {
    $transaction = FinancialTransaction::factory()->expense()->create();

    expect($transaction->type)->toBe(FinancialTransactionTypeEnum::Expense);
});

it('creates a paid transaction through the factory state', function (): void {
    $transaction = FinancialTransaction::factory()->paid()->create();

    expect($transaction->status)->toBe(FinancialTransactionStatusEnum::Paid);
});
