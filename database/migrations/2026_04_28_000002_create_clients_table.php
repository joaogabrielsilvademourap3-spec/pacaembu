<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('contact_name')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();
            $table->string('tiktok')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('service_plan')->nullable();
            $table->decimal('monthly_fee', 12, 2)->default(0);
            $table->string('status')->default('prospect');
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'company_name']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('clients');
    }
};
