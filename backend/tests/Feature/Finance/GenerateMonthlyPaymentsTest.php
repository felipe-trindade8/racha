<?php

use App\Models\FinancialTransaction;
use App\Models\Player;
use App\Models\User;

it('lets an administrator generate monthly payments', function (): void {
    $admin = User::factory()->administrator()->create();
    Player::factory()->count(2)->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->postJson('/api/v1/financial-transactions/monthly-payments', [
            'month' => '2026-06',
            'amount' => 50,
        ])
        ->assertCreated()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data' => [['id', 'playerId', 'description', 'amount', 'type', 'date', 'status']]]);

    expect(FinancialTransaction::count())->toBe(2);
});

it('does not duplicate when an administrator re-runs the same month', function (): void {
    $admin = User::factory()->administrator()->create();
    Player::factory()->count(2)->create();
    $token = $admin->createToken('api')->plainTextToken;
    $payload = ['month' => '2026-06', 'amount' => 50];

    $this->withToken($token)->postJson('/api/v1/financial-transactions/monthly-payments', $payload)->assertCreated();
    $this->withToken($token)->postJson('/api/v1/financial-transactions/monthly-payments', $payload)
        ->assertCreated()
        ->assertJsonCount(2, 'data');

    expect(FinancialTransaction::count())->toBe(2);
});

it('forbids a player from generating monthly payments', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $player->id]);

    $this->withToken($user->createToken('api')->plainTextToken)
        ->postJson('/api/v1/financial-transactions/monthly-payments', [
            'month' => '2026-06',
            'amount' => 50,
        ])
        ->assertStatus(403);

    expect(FinancialTransaction::count())->toBe(0);
});

it('requires authentication to generate monthly payments', function (): void {
    $this->postJson('/api/v1/financial-transactions/monthly-payments', [
        'month' => '2026-06',
        'amount' => 50,
    ])->assertStatus(401);
});

it('validates the payload', function (): void {
    $admin = User::factory()->administrator()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->postJson('/api/v1/financial-transactions/monthly-payments', [
            'month' => '2026/06',
            'amount' => 0,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['month', 'amount']);
});
