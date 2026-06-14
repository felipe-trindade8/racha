<?php

use App\Enums\FinancialTransactionStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            // Nullable: group-wide movements (field rental, equipment) have no
            // associated player. Detaching keeps the historical record intact.
            $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            // Stored as a positive decimal magnitude; `type` carries the
            // income/expense direction. Decimal keeps amounts display-friendly.
            $table->decimal('amount', 10, 2);
            $table->string('type');
            $table->date('date');
            $table->string('status')->default(FinancialTransactionStatusEnum::Open->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
