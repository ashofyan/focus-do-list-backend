<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->foreignId('milestone_id')
                ->nullable()
                ->after('group_id')
                ->constrained('milestones')
                ->nullOnDelete();

            $table->index(['user_id', 'milestone_id']);
        });
    }

    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropForeign(['milestone_id']);
            $table->dropIndex(['user_id', 'milestone_id']);
            $table->dropColumn('milestone_id');
        });
    }
};
