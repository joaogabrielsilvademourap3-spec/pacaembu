<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebsiteProject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['client_id','domain','hosting_provider','cms_platform','admin_url','project_stage','seo_checklist','performance_checklist','security_checklist','backup_status','maintenance_notes'];

    protected $casts = ['seo_checklist' => 'array','performance_checklist' => 'array','security_checklist' => 'array'];

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
}
