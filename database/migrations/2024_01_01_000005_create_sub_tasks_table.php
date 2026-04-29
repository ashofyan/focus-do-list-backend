<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('todo_id')->constrained('todos')->cascadeOnDelete();
            $table->string('title', 255);
            $table->boolean('is_completed')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index('todo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_tasks');
    }
};
