<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encrypted_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->text('encrypted_title')->nullable();
            $table->longText('encrypted_content');
            $table->string('note_iv', 512);
            $table->jsonb('note_tag')->nullable();
            $table->unsignedSmallInteger('encryption_version');
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->timestampTz('last_synced_at')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index('user_id');
            $table->index(['user_id', 'is_deleted', 'is_archived', 'is_pinned']);
            $table->index(['user_id', 'updated_at']);
            $table->index(['user_id', 'last_synced_at']);
            $table->index(['user_id', 'encryption_version']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX encrypted_notes_user_note_tag_gin ON encrypted_notes USING GIN (note_tag)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS encrypted_notes_user_note_tag_gin');
        }

        Schema::dropIfExists('encrypted_notes');
    }
};
