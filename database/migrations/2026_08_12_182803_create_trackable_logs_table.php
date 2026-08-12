<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trackable_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trackable_id')->constrained()->cascadeOnDelete();
            $table->decimal('value', 12, 2);
            $table->string('note')->nullable();
            $table->timestamp('logged_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trackable_logs');
    }
};
