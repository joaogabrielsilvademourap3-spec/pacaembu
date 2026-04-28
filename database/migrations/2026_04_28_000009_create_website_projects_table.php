<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('website_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('domain')->nullable();
            $table->string('hosting_provider')->nullable();
            $table->string('cms_platform')->nullable();
            $table->string('admin_url')->nullable();
            $table->string('project_stage')->nullable();
            $table->json('seo_checklist')->nullable();
            $table->json('performance_checklist')->nullable();
            $table->json('security_checklist')->nullable();
            $table->string('backup_status')->nullable();
            $table->text('maintenance_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void { Schema::dropIfExists('website_projects'); }
};
