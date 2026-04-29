<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();

            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->dateTime('due_date')->nullable();

            // Priority: low, medium, high
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');

            // Status
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');

            // Flags
            $table->boolean('is_pinned')->default(false);
            $table->dateTime('completed_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index('user_id');
            $table->index('group_id');
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'due_date']);
            $table->index(['user_id', 'is_pinned']);
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todos');
    }
};
