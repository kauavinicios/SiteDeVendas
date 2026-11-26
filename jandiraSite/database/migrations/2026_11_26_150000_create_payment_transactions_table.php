<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('provider', 40)->default('infinitepay');
            $table->string('driver', 20)->default('mock');
            $table->string('auth_mode', 20)->default('handle');
            $table->string('order_nsu', 64)->index();
            $table->string('slug', 120)->nullable()->index();
            $table->string('transaction_nsu', 120)->nullable()->index();
            $table->text('checkout_url')->nullable();
            $table->text('receipt_url')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->string('capture_method', 40)->nullable()->index();
            $table->unsignedSmallInteger('installments')->nullable();
            $table->unsignedInteger('amount_cents');
            $table->unsignedInteger('paid_amount_cents')->nullable();
            $table->timestamp('paid_at')->nullable()->index();
            $table->timestamp('last_checked_at')->nullable();
            $table->json('raw_create_payload')->nullable();
            $table->json('raw_last_check_payload')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
            $table->index(['provider', 'status']);
            $table->index(['capture_method', 'paid_at']);
        });

        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_transaction_id')->nullable()->constrained('payment_transactions')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('provider', 40)->default('infinitepay');
            $table->string('source', 30)->index();
            $table->string('event_type', 60)->index();
            $table->string('external_event_id', 120)->nullable()->index();
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
            $table->index(['payment_transaction_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');
        Schema::dropIfExists('payment_transactions');
    }
};
