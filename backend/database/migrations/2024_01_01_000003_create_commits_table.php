<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('repo_id')->constrained('repositories')->onDelete('cascade');
            $table->date('date');
            $table->text('message');
            $table->integer('additions')->default(0);
            $table->integer('deletions')->default(0);
            $table->integer('total_changes')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'date']);
            $table->index(['repo_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commits');
    }
};

