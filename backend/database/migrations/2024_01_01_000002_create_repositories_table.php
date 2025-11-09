<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repositories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('repo_name');
            $table->string('repo_url');
            $table->string('language')->nullable();
            $table->timestamp('last_commit_date')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'repo_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repositories');
    }
};

