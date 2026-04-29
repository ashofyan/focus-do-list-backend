<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 255);
            $table->string('category', 50)->nullable(); // Personal, Work, University, etc.
            $table->date('due_date');
            $table->unsignedTinyInteger('progress')->default(0); // 0-100
            $table->text('notes')->nullable();
            $table->string('color', 7)->default('#E8593C');
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index(['user_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milestones');
    }
};
