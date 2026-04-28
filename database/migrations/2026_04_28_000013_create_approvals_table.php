<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('content_calendar_id')->nullable()->constrained('content_calendars')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file')->nullable();
            $table->string('status')->default('pending');
            $table->text('client_comment')->nullable();
            $table->string('public_token')->unique();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status']);
        });
    }

    public function down(): void { Schema::dropIfExists('approvals'); }
};
