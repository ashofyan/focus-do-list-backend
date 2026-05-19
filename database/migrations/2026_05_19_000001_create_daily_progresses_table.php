<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_progresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreignId('todo_id')->nullable()->constrained('todos')->nullOnDelete();

            $table->date('progress_date');
            $table->string('title', 255);
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('todo_id');
            $table->index(['user_id', 'progress_date']);
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_progresses');
    }
};
