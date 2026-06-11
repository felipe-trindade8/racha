<?php

use App\Enums\PlayerStatusEnum;
use App\Http\Requests\Player\UpdatePlayerRequest;
use Database\Seeders\PositionSeeder;
use Illuminate\Support\Facades\Validator;

beforeEach(function (): void {
    $this->seed(PositionSeeder::class);
});

function validateUpdate(array $data)
{
    return Validator::make($data, (new UpdatePlayerRequest)->rules());
}

it('passes with an empty payload (partial update)', function (): void {
    expect(validateUpdate([])->passes())->toBeTrue();
});

it('passes when only a single field is provided', function (): void {
    expect(validateUpdate(['rating' => 3])->passes())->toBeTrue();
});

it('rejects an invalid rating when present', function (): void {
    expect(validateUpdate(['rating' => 9])->errors()->has('rating'))->toBeTrue();
});

it('rejects an unknown status when present', function (): void {
    expect(validateUpdate(['status' => 'retired'])->errors()->has('status'))->toBeTrue();
});

it('accepts a valid status when present', function (): void {
    expect(validateUpdate(['status' => PlayerStatusEnum::Inactive->value])->passes())->toBeTrue();
});

it('rejects an unknown position id when present', function (): void {
    expect(validateUpdate(['position_ids' => [9999]])->errors()->has('position_ids.0'))->toBeTrue();
});

it('accepts an empty position_ids array to clear positions', function (): void {
    expect(validateUpdate(['position_ids' => []])->passes())->toBeTrue();
});
