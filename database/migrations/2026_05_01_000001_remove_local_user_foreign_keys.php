<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'groups',
        'labels',
        'todos',
        'milestones',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'user_id')) {
                continue;
            }

            $this->dropUserForeignKey($table);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'user_id')) {
                continue;
            }

            $this->addUserForeignKey($table);
        }
    }

    private function dropUserForeignKey(string $table): void
    {
        $constraint = $table . '_user_id_foreign';
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement(sprintf('ALTER TABLE "%s" DROP CONSTRAINT IF EXISTS "%s"', $table, $constraint));

            return;
        }

        if ($driver === 'mysql') {
            $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
                ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', $table)
                ->where('CONSTRAINT_NAME', $constraint)
                ->exists();

            if ($exists) {
                DB::statement(sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $table, $constraint));
            }
        }
    }

    private function addUserForeignKey(string $table): void
    {
        $constraint = $table . '_user_id_foreign';
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement(sprintf(
                'ALTER TABLE "%s" ADD CONSTRAINT "%s" FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON DELETE CASCADE',
                $table,
                $constraint
            ));

            return;
        }

        if ($driver === 'mysql') {
            DB::statement(sprintf(
                'ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE',
                $table,
                $constraint
            ));
        }
    }
};
