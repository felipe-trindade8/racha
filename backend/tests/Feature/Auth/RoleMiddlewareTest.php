<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::middleware(['api', 'role:administrator'])
        ->get('api/test/admin-only', fn () => response()->json(['status' => 'ok']));

    Route::middleware(['api', 'role:administrator,player'])
        ->get('api/test/members-only', fn () => response()->json(['status' => 'ok']));
});

it('allows an administrator through an administrator-only route', function (): void {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)
        ->getJson('/api/test/admin-only')
        ->assertOk()
        ->assertExactJson(['status' => 'ok']);
});

it('blocks a non-administrator with a 403 in the standard envelope', function (): void {
    $player = User::factory()->create();

    $this->actingAs($player)
        ->getJson('/api/test/admin-only')
        ->assertStatus(403)
        ->assertJsonStructure(['message']);
});

it('blocks an unauthenticated request with a 401 in the standard envelope', function (): void {
    $this->getJson('/api/test/admin-only')
        ->assertStatus(401)
        ->assertJsonStructure(['message']);
});

it('allows a player through a route that lists the player role', function (): void {
    $player = User::factory()->create();

    $this->actingAs($player)
        ->getJson('/api/test/members-only')
        ->assertOk()
        ->assertExactJson(['status' => 'ok']);
});

it('allows an administrator through a multi-role route', function (): void {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)
        ->getJson('/api/test/members-only')
        ->assertOk();
});
