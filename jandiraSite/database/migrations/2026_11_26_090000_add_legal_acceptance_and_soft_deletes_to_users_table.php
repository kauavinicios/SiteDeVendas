<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('terms_accepted_at')->nullable()->after('marketing_opt_out_at');
            $table->string('terms_version', 20)->nullable()->after('terms_accepted_at');
            $table->timestamp('privacy_accepted_at')->nullable()->after('terms_version');
            $table->string('privacy_version', 20)->nullable()->after('privacy_accepted_at');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'terms_accepted_at',
                'terms_version',
                'privacy_accepted_at',
                'privacy_version',
            ]);
        });
    }
};
