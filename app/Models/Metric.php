<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Metric extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['client_id','month','followers','reach','impressions','engagement','clicks','leads','conversions','website_traffic','notes'];

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
}
