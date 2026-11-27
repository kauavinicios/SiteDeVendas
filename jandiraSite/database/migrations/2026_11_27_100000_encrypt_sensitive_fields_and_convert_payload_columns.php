<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE payment_transactions ALTER COLUMN raw_create_payload TYPE text USING raw_create_payload::text');
            DB::statement('ALTER TABLE payment_transactions ALTER COLUMN raw_last_check_payload TYPE text USING raw_last_check_payload::text');
            DB::statement('ALTER TABLE payment_events ALTER COLUMN payload TYPE text USING payload::text');
        } else {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->text('raw_create_payload')->nullable()->change();
                $table->text('raw_last_check_payload')->nullable()->change();
            });

            Schema::table('payment_events', function (Blueprint $table) {
                $table->text('payload')->nullable()->change();
            });
        }

        $this->encryptExistingColumns('payment_transactions', [
            'checkout_url',
            'receipt_url',
            'raw_create_payload',
            'raw_last_check_payload',
        ]);

        $this->encryptExistingColumns('payment_events', [
            'payload',
        ]);

        $this->encryptExistingColumns('orders', [
            'notes',
        ]);

        $this->encryptExistingColumns('contact_messages', [
            'message',
        ]);
    }

    public function down(): void
    {
        $this->decryptExistingColumns('payment_transactions', [
            'checkout_url',
            'receipt_url',
            'raw_create_payload',
            'raw_last_check_payload',
        ]);

        $this->decryptExistingColumns('payment_events', [
            'payload',
        ]);

        $this->decryptExistingColumns('orders', [
            'notes',
        ]);

        $this->decryptExistingColumns('contact_messages', [
            'message',
        ]);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE payment_transactions ALTER COLUMN raw_create_payload TYPE json USING NULLIF(raw_create_payload, '')::json");
            DB::statement("ALTER TABLE payment_transactions ALTER COLUMN raw_last_check_payload TYPE json USING NULLIF(raw_last_check_payload, '')::json");
            DB::statement("ALTER TABLE payment_events ALTER COLUMN payload TYPE json USING NULLIF(payload, '')::json");
        } else {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->json('raw_create_payload')->nullable()->change();
                $table->json('raw_last_check_payload')->nullable()->change();
            });

            Schema::table('payment_events', function (Blueprint $table) {
                $table->json('payload')->nullable()->change();
            });
        }
    }

    /**
     * @param  list<string>  $columns
     */
    private function encryptExistingColumns(string $table, array $columns): void
    {
        DB::table($table)
            ->select(array_merge(['id'], $columns))
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($table, $columns): void {
                foreach ($rows as $row) {
                    $updates = [];

                    foreach ($columns as $column) {
                        $value = $row->{$column};

                        if ($value === null || $value === '') {
                            continue;
                        }

                        if (! $this->isAlreadyEncrypted((string) $value)) {
                            $updates[$column] = Crypt::encryptString((string) $value);
                        }
                    }

                    if ($updates !== []) {
                        DB::table($table)->where('id', $row->id)->update($updates);
                    }
                }
            });
    }

    /**
     * @param  list<string>  $columns
     */
    private function decryptExistingColumns(string $table, array $columns): void
    {
        DB::table($table)
            ->select(array_merge(['id'], $columns))
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($table, $columns): void {
                foreach ($rows as $row) {
                    $updates = [];

                    foreach ($columns as $column) {
                        $value = $row->{$column};

                        if ($value === null || $value === '') {
                            continue;
                        }

                        try {
                            $updates[$column] = Crypt::decryptString((string) $value);
                        } catch (DecryptException) {
                            // Registro já em texto plano ou não legível com a chave atual.
                        }
                    }

                    if ($updates !== []) {
                        DB::table($table)->where('id', $row->id)->update($updates);
                    }
                }
            });
    }

    private function isAlreadyEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
};
