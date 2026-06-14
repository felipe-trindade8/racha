<?php

use App\Enums\FinancialTransactionStatusEnum;
use App\Enums\FinancialTransactionTypeEnum;
use App\Models\Player;
use App\Services\FinancialTransactionService;

beforeEach(function (): void {
    $this->service = app(FinancialTransactionService::class);

    $this->baseData = [
        'description' => 'Field rental',
        'amount' => 200,
        'type' => FinancialTransactionTypeEnum::Expense,
        'date' => '2026-06-14',
    ];
});

it('creates an income transaction', function (): void {
    $transaction = $this->service->create([
        ...$this->baseData,
        'type' => FinancialTransactionTypeEnum::Income,
    ]);

    expect($transaction->exists)->toBeTrue()
        ->and($transaction->type)->toBe(FinancialTransactionTypeEnum::Income);
});

it('creates an expense transaction', function (): void {
    $transaction = $this->service->create($this->baseData);

    expect($transaction->type)->toBe(FinancialTransactionTypeEnum::Expense);
});

it('defaults a new transaction to the open status', function (): void {
    $transaction = $this->service->create($this->baseData);

    expect($transaction->status)->toBe(FinancialTransactionStatusEnum::Open);
});

it('associates a player when a player_id is provided', function (): void {
    $player = Player::factory()->create();

    $transaction = $this->service->create([...$this->baseData, 'player_id' => $player->id]);

    expect($transaction->player_id)->toBe($player->id);
});

it('leaves the player null when no player_id is provided', function (): void {
    $transaction = $this->service->create($this->baseData);

    expect($transaction->player_id)->toBeNull();
});

it('updates a transaction attributes', function (): void {
    $transaction = $this->service->create($this->baseData);

    $this->service->update($transaction, [
        'description' => 'Referee fee',
        'amount' => 80,
        'type' => FinancialTransactionTypeEnum::Income,
    ]);

    $transaction = $transaction->fresh();

    expect($transaction->description)->toBe('Referee fee')
        ->and($transaction->amount)->toBe('80.00')
        ->and($transaction->type)->toBe(FinancialTransactionTypeEnum::Income);
});

it('transitions a transaction to the paid status', function (): void {
    $transaction = $this->service->create($this->baseData);

    $this->service->updateStatus($transaction, FinancialTransactionStatusEnum::Paid);

    expect($transaction->fresh()->status)->toBe(FinancialTransactionStatusEnum::Paid);
});

it('transitions a transaction back to the open status', function (): void {
    $transaction = $this->service->create($this->baseData);
    $this->service->updateStatus($transaction, FinancialTransactionStatusEnum::Paid);

    $this->service->updateStatus($transaction, FinancialTransactionStatusEnum::Open);

    expect($transaction->fresh()->status)->toBe(FinancialTransactionStatusEnum::Open);
});
