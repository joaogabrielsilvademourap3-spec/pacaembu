<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('content_calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('platform');
            $table->string('content_type');
            $table->string('title');
            $table->text('caption')->nullable();
            $table->text('creative_briefing')->nullable();
            $table->text('references')->nullable();
            $table->date('publish_date')->nullable();
            $table->string('status')->default('draft');
            $table->string('attached_file')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['client_id','status','publish_date']);
        });
    }

    public function down(): void { Schema::dropIfExists('content_calendars'); }
};
