<?php

use App\Models\FinancialTransaction;
use App\Models\Player;
use App\Models\User;

it('lets an administrator update a transaction', function (): void {
    $admin = User::factory()->administrator()->create();
    $transaction = FinancialTransaction::factory()->create([
        'description' => 'Original',
        'amount' => 10,
    ]);

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->putJson("/api/v1/financial-transactions/{$transaction->id}", [
            'description' => 'Updated',
            'amount' => 75,
        ])
        ->assertOk()
        ->assertJsonPath('data.description', 'Updated')
        ->assertJsonPath('data.amount', '75.00');

    expect($transaction->fresh()->description)->toBe('Updated');
});

it('forbids editing a paid transaction', function (): void {
    $admin = User::factory()->administrator()->create();
    $transaction = FinancialTransaction::factory()->paid()->create(['description' => 'Settled']);

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->putJson("/api/v1/financial-transactions/{$transaction->id}", [
            'description' => 'Changed',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['status']);

    expect($transaction->fresh()->description)->toBe('Settled');
});

it('allows editing again once a paid transaction is reopened', function (): void {
    $admin = User::factory()->administrator()->create();
    $token = $admin->createToken('api')->plainTextToken;
    $transaction = FinancialTransaction::factory()->paid()->create(['description' => 'Original']);

    $this->withToken($token)
        ->patchJson("/api/v1/financial-transactions/{$transaction->id}/status", ['status' => 'open'])
        ->assertOk();

    $this->withToken($token)
        ->putJson("/api/v1/financial-transactions/{$transaction->id}", ['description' => 'Edited'])
        ->assertOk();

    expect($transaction->fresh()->description)->toBe('Edited');
});

it('forbids a player from updating a transaction', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $player->id]);
    $transaction = FinancialTransaction::factory()->create();

    $this->withToken($user->createToken('api')->plainTextToken)
        ->putJson("/api/v1/financial-transactions/{$transaction->id}", [
            'description' => 'Hacked',
        ])
        ->assertStatus(403);
});

it('requires authentication to update a transaction', function (): void {
    $transaction = FinancialTransaction::factory()->create();

    $this->putJson("/api/v1/financial-transactions/{$transaction->id}", [])
        ->assertStatus(401);
});

it('returns 404 for an unknown transaction', function (): void {
    $admin = User::factory()->administrator()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->putJson('/api/v1/financial-transactions/999', ['description' => 'X'])
        ->assertStatus(404);
});

it('validates the payload', function (): void {
    $admin = User::factory()->administrator()->create();
    $transaction = FinancialTransaction::factory()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->putJson("/api/v1/financial-transactions/{$transaction->id}", [
            'amount' => 0,
            'type' => 'foo',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['amount', 'type']);
});
