<?php

namespace Database\Factories;

use App\Enums\FinancialTransactionStatusEnum;
use App\Enums\FinancialTransactionTypeEnum;
use App\Models\FinancialTransaction;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialTransaction>
 */
class FinancialTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'player_id' => null,
            'description' => fake()->sentence(3),
            'amount' => fake()->randomFloat(2, 1, 1000),
            'type' => fake()->randomElement(FinancialTransactionTypeEnum::cases()),
            'date' => fake()->date(),
            'status' => FinancialTransactionStatusEnum::Open,
        ];
    }

    /**
     * Indicate that the transaction is an income.
     */
    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => FinancialTransactionTypeEnum::Income,
        ]);
    }

    /**
     * Indicate that the transaction is an expense.
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => FinancialTransactionTypeEnum::Expense,
        ]);
    }

    /**
     * Indicate that the transaction has been paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FinancialTransactionStatusEnum::Paid,
        ]);
    }

    /**
     * Associate the transaction with a player.
     */
    public function forPlayer(Player $player): static
    {
        return $this->state(fn (array $attributes) => [
            'player_id' => $player->id,
        ]);
    }
}
