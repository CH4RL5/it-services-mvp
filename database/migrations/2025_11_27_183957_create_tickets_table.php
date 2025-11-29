<?php

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
    Schema::create('tickets', function (Blueprint $table) {
        $table->id();
        $table->uuid('uuid')->unique(); // ID público y seguro
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('expert_id')->nullable()->constrained('users');

        $table->string('title')->nullable();
        $table->text('description'); // El problema
        $table->string('category')->nullable(); // DNS, Server, etc.
        $table->string('status')->default('pending_payment');

        // Pagos Stripe
        $table->string('stripe_session_id')->nullable();
        $table->decimal('amount', 10, 2)->nullable();
        $table->boolean('is_paid')->default(false);

        $table->timestamp('assigned_at')->nullable();
        $table->timestamp('closed_at')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
