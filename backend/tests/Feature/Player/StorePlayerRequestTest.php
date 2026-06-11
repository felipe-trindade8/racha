<?php

use App\Enums\PlayerStatusEnum;
use App\Http\Requests\Player\StorePlayerRequest;
use App\Models\Position;
use Database\Seeders\PositionSeeder;
use Illuminate\Support\Facades\Validator;

beforeEach(function (): void {
    $this->seed(PositionSeeder::class);
});

function validateStore(array $data)
{
    return Validator::make($data, (new StorePlayerRequest)->rules());
}

function validStorePayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Ronaldo',
        'rating' => 5,
        'status' => PlayerStatusEnum::Active->value,
    ], $overrides);
}

it('passes with a valid payload', function (): void {
    $data = validStorePayload([
        'position_ids' => [Position::where('code', 'FWD')->value('id')],
    ]);

    expect(validateStore($data)->passes())->toBeTrue();
});

it('requires a name', function (): void {
    $data = validStorePayload();
    unset($data['name']);

    expect(validateStore($data)->errors()->has('name'))->toBeTrue();
});

it('rejects a rating outside the 1 to 5 range', function (int $rating): void {
    expect(validateStore(validStorePayload(['rating' => $rating]))->errors()->has('rating'))
        ->toBeTrue();
})->with([0, 6, 10, -1]);

it('rejects an unknown status', function (): void {
    expect(validateStore(validStorePayload(['status' => 'retired']))->errors()->has('status'))
        ->toBeTrue();
});

it('rejects a position id that does not exist', function (): void {
    $data = validStorePayload(['position_ids' => [9999]]);

    expect(validateStore($data)->errors()->has('position_ids.0'))->toBeTrue();
});
