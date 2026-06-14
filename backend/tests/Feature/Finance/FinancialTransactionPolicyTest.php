<?php

use App\Models\FinancialTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

it('lets administrators perform every financial transaction action', function (string $ability): void {
    $admin = User::factory()->administrator()->create();
    $transaction = FinancialTransaction::factory()->create();

    expect(Gate::forUser($admin)->allows($ability, $transaction))->toBeTrue();
})->with(['viewAny', 'view', 'create', 'update', 'updateStatus', 'delete']);

it('forbids a player from every financial transaction action', function (string $ability): void {
    $player = User::factory()->create();
    $transaction = FinancialTransaction::factory()->create();

    expect(Gate::forUser($player)->allows($ability, $transaction))->toBeFalse();
})->with(['viewAny', 'view', 'create', 'update', 'updateStatus', 'delete']);
