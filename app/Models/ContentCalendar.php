<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentCalendar extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'content_calendars';

    protected $fillable = ['client_id','platform','content_type','title','caption','creative_briefing','references','publish_date','status','attached_file'];

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
}
