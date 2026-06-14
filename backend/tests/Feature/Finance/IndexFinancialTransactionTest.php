<?php

use App\Models\FinancialTransaction;
use App\Models\Player;
use App\Models\User;

beforeEach(function (): void {
    $this->adminToken = User::factory()->administrator()->create()->createToken('api')->plainTextToken;
});

it('returns a paginated, camelCase list for an administrator', function (): void {
    $player = Player::factory()->create();
    FinancialTransaction::factory()->count(3)->forPlayer($player)->create(['date' => '2026-06-10']);

    $this->withToken($this->adminToken)
        ->getJson('/api/v1/financial-transactions')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [['id', 'playerId', 'description', 'amount', 'type', 'date', 'status', 'createdAt', 'updatedAt']],
            'meta' => ['current_page', 'per_page', 'total', 'last_page'],
        ])
        ->assertJsonPath('meta.total', 3);
});

it('paginates with per_page', function (): void {
    FinancialTransaction::factory()->count(5)->create();

    $this->withToken($this->adminToken)
        ->getJson('/api/v1/financial-transactions?per_page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.last_page', 3);
});

it('filters by type', function (): void {
    FinancialTransaction::factory()->income()->count(2)->create();
    FinancialTransaction::factory()->expense()->count(3)->create();

    $this->withToken($this->adminToken)
        ->getJson('/api/v1/financial-transactions?type=income')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 2);
});

it('filters by status', function (): void {
    FinancialTransaction::factory()->count(2)->create();
    FinancialTransaction::factory()->paid()->count(4)->create();

    $this->withToken($this->adminToken)
        ->getJson('/api/v1/financial-transactions?status=paid')
        ->assertOk()
        ->assertJsonCount(4, 'data');
});

it('filters by month', function (): void {
    FinancialTransaction::factory()->count(2)->create(['date' => '2026-06-15']);
    FinancialTransaction::factory()->count(3)->create(['date' => '2026-07-01']);

    $this->withToken($this->adminToken)
        ->getJson('/api/v1/financial-transactions?month=2026-06')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters by player', function (): void {
    $player = Player::factory()->create();
    FinancialTransaction::factory()->forPlayer($player)->count(2)->create();
    FinancialTransaction::factory()->count(3)->create();

    $this->withToken($this->adminToken)
        ->getJson("/api/v1/financial-transactions?player_id={$player->id}")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('validates the filters', function (): void {
    $this->withToken($this->adminToken)
        ->getJson('/api/v1/financial-transactions?type=foo&month=2026/06&per_page=0')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['type', 'month', 'per_page']);
});

it('forbids a player from listing transactions', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $player->id]);

    $this->withToken($user->createToken('api')->plainTextToken)
        ->getJson('/api/v1/financial-transactions')
        ->assertStatus(403);
});

it('requires authentication to list transactions', function (): void {
    $this->getJson('/api/v1/financial-transactions')->assertStatus(401);
});
