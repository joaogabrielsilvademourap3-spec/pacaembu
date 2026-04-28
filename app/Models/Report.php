<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['client_id','month','completed_tasks','published_content','project_progress','metrics_comparison','payments_summary','executive_summary','generated_by'];

    protected $casts = ['completed_tasks' => 'array','published_content' => 'array','project_progress' => 'array','metrics_comparison' => 'array','payments_summary' => 'array'];

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function generator(): BelongsTo { return $this->belongsTo(User::class, 'generated_by'); }
}
