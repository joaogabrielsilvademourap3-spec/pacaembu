<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id','title','description','category','status','priority','deadline','responsible_user_id','budget','notes',
    ];

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function responsibleUser(): BelongsTo { return $this->belongsTo(User::class, 'responsible_user_id'); }
    public function tasks(): HasMany { return $this->hasMany(Task::class); }
    public function files(): HasMany { return $this->hasMany(AgencyFile::class); }
    public function approvals(): HasMany { return $this->hasMany(Approval::class); }
}
