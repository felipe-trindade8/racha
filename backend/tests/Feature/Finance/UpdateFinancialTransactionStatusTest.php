<?php

use App\Enums\FinancialTransactionStatusEnum;
use App\Models\FinancialTransaction;
use App\Models\Player;
use App\Models\User;

it('lets an administrator mark a transaction as paid', function (): void {
    $admin = User::factory()->administrator()->create();
    $transaction = FinancialTransaction::factory()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->patchJson("/api/v1/financial-transactions/{$transaction->id}/status", ['status' => 'paid'])
        ->assertOk()
        ->assertJsonPath('data.status', FinancialTransactionStatusEnum::Paid->value);

    expect($transaction->fresh()->status)->toBe(FinancialTransactionStatusEnum::Paid);
});

it('lets an administrator reopen a paid transaction', function (): void {
    $admin = User::factory()->administrator()->create();
    $transaction = FinancialTransaction::factory()->paid()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->patchJson("/api/v1/financial-transactions/{$transaction->id}/status", ['status' => 'open'])
        ->assertOk()
        ->assertJsonPath('data.status', FinancialTransactionStatusEnum::Open->value);

    expect($transaction->fresh()->status)->toBe(FinancialTransactionStatusEnum::Open);
});

it('validates the status', function (): void {
    $admin = User::factory()->administrator()->create();
    $transaction = FinancialTransaction::factory()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->patchJson("/api/v1/financial-transactions/{$transaction->id}/status", ['status' => 'foo'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

it('forbids a player from changing a transaction status', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $player->id]);
    $transaction = FinancialTransaction::factory()->create();

    $this->withToken($user->createToken('api')->plainTextToken)
        ->patchJson("/api/v1/financial-transactions/{$transaction->id}/status", ['status' => 'paid'])
        ->assertStatus(403);

    expect($transaction->fresh()->status)->toBe(FinancialTransactionStatusEnum::Open);
});

it('requires authentication to change a transaction status', function (): void {
    $transaction = FinancialTransaction::factory()->create();

    $this->patchJson("/api/v1/financial-transactions/{$transaction->id}/status", ['status' => 'paid'])
        ->assertStatus(401);
});
