<?php

use App\Enums\FinancialTransactionStatusEnum;
use App\Models\FinancialTransaction;
use App\Models\Player;
use App\Models\User;

it('lets an administrator create a transaction that starts open', function (): void {
    $admin = User::factory()->administrator()->create();
    $player = Player::factory()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->postJson('/api/v1/financial-transactions', [
            'player_id' => $player->id,
            'description' => 'Monthly fee',
            'amount' => 50,
            'type' => 'income',
            'date' => '2026-06-14',
        ])
        ->assertCreated()
        ->assertJsonPath('data.description', 'Monthly fee')
        ->assertJsonPath('data.playerId', $player->id)
        ->assertJsonPath('data.status', FinancialTransactionStatusEnum::Open->value);

    expect(FinancialTransaction::where('description', 'Monthly fee')->sole()->status)
        ->toBe(FinancialTransactionStatusEnum::Open);
});

it('allows a group-wide transaction with no player', function (): void {
    $admin = User::factory()->administrator()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->postJson('/api/v1/financial-transactions', [
            'description' => 'Field rental',
            'amount' => 200,
            'type' => 'expense',
            'date' => '2026-06-14',
        ])
        ->assertCreated()
        ->assertJsonPath('data.playerId', null);
});

it('forbids a player from creating a transaction', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $player->id]);

    $this->withToken($user->createToken('api')->plainTextToken)
        ->postJson('/api/v1/financial-transactions', [
            'description' => 'Monthly fee',
            'amount' => 50,
            'type' => 'income',
            'date' => '2026-06-14',
        ])
        ->assertStatus(403);

    expect(FinancialTransaction::count())->toBe(0);
});

it('requires authentication to create a transaction', function (): void {
    $this->postJson('/api/v1/financial-transactions', [])->assertStatus(401);
});

it('validates the payload', function (): void {
    $admin = User::factory()->administrator()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->postJson('/api/v1/financial-transactions', [
            'amount' => 0,
            'type' => 'foo',
            'date' => '14-06-2026',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['description', 'amount', 'type', 'date']);
});
