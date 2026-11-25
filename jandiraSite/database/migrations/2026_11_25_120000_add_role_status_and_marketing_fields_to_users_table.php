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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('customer')->index();
            $table->boolean('active')->default(true)->index();
            $table->string('phone', 20)->nullable();
            $table->boolean('marketing_opt_in')->default(false)->index();
            $table->timestamp('marketing_opt_in_at')->nullable();
            $table->timestamp('marketing_opt_out_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'active',
                'phone',
                'marketing_opt_in',
                'marketing_opt_in_at',
                'marketing_opt_out_at',
            ]);
        });
    }
};
