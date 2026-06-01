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
        Schema::create('meetup_schedule_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meetup_id')->constrained()->cascadeOnDelete();
            $table->string('time', 10);
            $table->string('title');
            $table->string('note')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetup_schedule_items');
    }
};
