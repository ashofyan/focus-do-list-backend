<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('reminders');

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'telegram_chat_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['telegram_chat_id']);
                $table->dropIndex(['telegram_chat_id']);
                $table->dropColumn([
                    'telegram_chat_id',
                    'telegram_username',
                    'telegram_connected',
                ]);
            });
        }
    }

    public function down(): void
    {
        //
    }
};
