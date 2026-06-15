<?php

use App\Models\FinancialTransaction;
use App\Models\Player;
use App\Models\User;

beforeEach(function (): void {
    $this->adminToken = User::factory()->administrator()->create()->createToken('api')->plainTextToken;
});

it('computes paid totals and balance by default', function (): void {
    FinancialTransaction::factory()->income()->paid()->create(['amount' => 100, 'date' => '2026-06-10']);
    FinancialTransaction::factory()->expense()->paid()->create(['amount' => 40, 'date' => '2026-06-12']);
    // Open transactions must be ignored by the default (paid) view.
    FinancialTransaction::factory()->income()->create(['amount' => 999, 'date' => '2026-06-15']);

    $this->withToken($this->adminToken)
        ->getJson('/api/v1/financial-transactions/cash-flow')
        ->assertOk()
        ->assertJsonPath('data.status', 'paid')
        ->assertJsonPath('data.totals.income', '100.00')
        ->assertJsonPath('data.totals.expense', '40.00')
        ->assertJsonPath('data.totals.balance', '60.00');
});

it('reports open transactions when status=open', function (): void {
    FinancialTransaction::factory()->income()->paid()->create(['amount' => 100, 'date' => '2026-06-10']);
    FinancialTransaction::factory()->income()->create(['amount' => 30, 'date' => '2026-06-11']);
    FinancialTransaction::factory()->expense()->create(['amount' => 10, 'date' => '2026-06-12']);

    $this->withToken($this->adminToken)
        ->getJson('/api/v1/financial-transactions/cash-flow?status=open')
        ->assertOk()
        ->assertJsonPath('data.status', 'open')
        ->assertJsonPath('data.totals.income', '30.00')
        ->assertJsonPath('data.totals.expense', '10.00')
        ->assertJsonPath('data.totals.balance', '20.00');
});

it('reports every status when status=all', function (): void {
    FinancialTransaction::factory()->income()->paid()->create(['amount' => 100, 'date' => '2026-06-10']);
    FinancialTransaction::factory()->income()->create(['amount' => 30, 'date' => '2026-06-11']);

    $this->withToken($this->adminToken)
        ->getJson('/api/v1/financial-transactions/cash-flow?status=all')
        ->assertOk()
        ->assertJsonPath('data.status', 'all')
        ->assertJsonPath('data.totals.income', '130.00')
        ->assertJsonPath('data.totals.balance', '130.00');
});

it('returns a monthly breakdown ordered by month', function (): void {
    FinancialTransaction::factory()->income()->paid()->create(['amount' => 100, 'date' => '2026-06-10']);
    FinancialTransaction::factory()->expense()->paid()->create(['amount' => 25, 'date' => '2026-06-20']);
    FinancialTransaction::factory()->income()->paid()->create(['amount' => 80, 'date' => '2026-07-05']);

    $this->withToken($this->adminToken)
        ->getJson('/api/v1/financial-transactions/cash-flow')
        ->assertOk()
        ->assertJsonCount(2, 'data.monthly')
        ->assertJsonPath('data.monthly.0', ['month' => '2026-06', 'income' => '100.00', 'expense' => '25.00', 'balance' => '75.00'])
        ->assertJsonPath('data.monthly.1', ['month' => '2026-07', 'income' => '80.00', 'expense' => '0.00', 'balance' => '80.00']);
});

it('restricts totals and breakdown to the requested range', function (): void {
    FinancialTransaction::factory()->income()->paid()->create(['amount' => 50, 'date' => '2026-05-30']);
    FinancialTransaction::factory()->income()->paid()->create(['amount' => 100, 'date' => '2026-06-15']);
    FinancialTransaction::factory()->income()->paid()->create(['amount' => 200, 'date' => '2026-08-01']);

    $this->withToken($this->adminToken)
        ->getJson('/api/v1/financial-transactions/cash-flow?from=2026-06&to=2026-07')
        ->assertOk()
        ->assertJsonPath('data.range', ['from' => '2026-06', 'to' => '2026-07'])
        ->assertJsonPath('data.totals.income', '100.00')
        ->assertJsonCount(1, 'data.monthly');
});

it('returns zeroed totals and an empty breakdown when there is no data', function (): void {
    $this->withToken($this->adminToken)
        ->getJson('/api/v1/financial-transactions/cash-flow')
        ->assertOk()
        ->assertJsonPath('data.totals', ['income' => '0.00', 'expense' => '0.00', 'balance' => '0.00'])
        ->assertJsonCount(0, 'data.monthly');
});

it('validates the query parameters', function (): void {
    $this->withToken($this->adminToken)
        ->getJson('/api/v1/financial-transactions/cash-flow?from=2026-06&to=2026-01&status=foo')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['to', 'status']);
});

it('forbids a player from viewing the cash flow', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $player->id]);

    $this->withToken($user->createToken('api')->plainTextToken)
        ->getJson('/api/v1/financial-transactions/cash-flow')
        ->assertStatus(403);
});

it('requires authentication to view the cash flow', function (): void {
    $this->getJson('/api/v1/financial-transactions/cash-flow')->assertStatus(401);
});
