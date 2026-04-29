<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todo_label', function (Blueprint $table) {
            $table->foreignId('todo_id')->constrained('todos')->cascadeOnDelete();
            $table->foreignId('label_id')->constrained('labels')->cascadeOnDelete();

            $table->primary(['todo_id', 'label_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todo_label');
    }
};
