<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('week_start');
            $table->date('week_end');
            $table->integer('commits_count')->default(0);
            $table->integer('total_additions')->default(0);
            $table->integer('total_deletions')->default(0);
            $table->string('top_repo')->nullable();
            $table->string('top_language')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'week_start']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_stats');
    }
};

