<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->date('month');
            $table->json('completed_tasks')->nullable();
            $table->json('published_content')->nullable();
            $table->json('project_progress')->nullable();
            $table->json('metrics_comparison')->nullable();
            $table->json('payments_summary')->nullable();
            $table->longText('executive_summary')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void { Schema::dropIfExists('reports'); }
};
