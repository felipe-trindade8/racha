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

it('accepts the type as a backing string value', function (): void {
    $transaction = $this->service->create([
        ...$this->baseData,
        'type' => 'income',
    ]);

    expect($transaction->type)->toBe(FinancialTransactionTypeEnum::Income);
});

it('rejects a non-positive amount', function (mixed $amount): void {
    $this->service->create([...$this->baseData, 'amount' => $amount]);
})->with([0, -1, -200.50])->throws(InvalidArgumentException::class);

it('rejects a non-numeric amount', function (): void {
    $this->service->create([...$this->baseData, 'amount' => 'free']);
})->throws(InvalidArgumentException::class);

it('rejects an invalid type', function (): void {
    $this->service->create([...$this->baseData, 'type' => 'refund']);
})->throws(InvalidArgumentException::class);

it('associates a player when a player_id is provided', function (): void {
    $player = Player::factory()->create();

    $transaction = $this->service->create([...$this->baseData, 'player_id' => $player->id]);

    expect($transaction->player_id)->toBe($player->id);
});

it('leaves the player null when no player_id is provided', function (): void {
    $transaction = $this->service->create($this->baseData);

    expect($transaction->player_id)->toBeNull();
});

it('marks a transaction as paid', function (): void {
    $transaction = $this->service->create($this->baseData);

    $this->service->markAsPaid($transaction);

    expect($transaction->fresh()->status)->toBe(FinancialTransactionStatusEnum::Paid);
});

it('marks a transaction back as open', function (): void {
    $transaction = $this->service->create($this->baseData);
    $this->service->markAsPaid($transaction);

    $this->service->markAsOpen($transaction);

    expect($transaction->fresh()->status)->toBe(FinancialTransactionStatusEnum::Open);
});
