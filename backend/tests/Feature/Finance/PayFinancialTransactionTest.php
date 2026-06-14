<?php

use App\Enums\FinancialTransactionStatusEnum;
use App\Models\FinancialTransaction;
use App\Models\Player;
use App\Models\User;

it('lets an administrator mark a transaction as paid', function (): void {
    $admin = User::factory()->administrator()->create();
    $transaction = FinancialTransaction::factory()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->patchJson("/api/v1/financial-transactions/{$transaction->id}/pay")
        ->assertOk()
        ->assertJsonPath('data.status', FinancialTransactionStatusEnum::Paid->value);

    expect($transaction->fresh()->status)->toBe(FinancialTransactionStatusEnum::Paid);
});

it('is idempotent when already paid', function (): void {
    $admin = User::factory()->administrator()->create();
    $transaction = FinancialTransaction::factory()->paid()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->patchJson("/api/v1/financial-transactions/{$transaction->id}/pay")
        ->assertOk()
        ->assertJsonPath('data.status', FinancialTransactionStatusEnum::Paid->value);
});

it('forbids a player from paying a transaction', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $player->id]);
    $transaction = FinancialTransaction::factory()->create();

    $this->withToken($user->createToken('api')->plainTextToken)
        ->patchJson("/api/v1/financial-transactions/{$transaction->id}/pay")
        ->assertStatus(403);

    expect($transaction->fresh()->status)->toBe(FinancialTransactionStatusEnum::Open);
});

it('requires authentication to pay a transaction', function (): void {
    $transaction = FinancialTransaction::factory()->create();

    $this->patchJson("/api/v1/financial-transactions/{$transaction->id}/pay")
        ->assertStatus(401);
});
