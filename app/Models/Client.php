<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_name','contact_name','whatsapp','email','website','instagram','facebook','tiktok','linkedin',
        'service_plan','monthly_fee','status','internal_notes',
    ];

    public function projects(): HasMany { return $this->hasMany(Project::class); }
    public function tasks(): HasMany { return $this->hasMany(Task::class); }
    public function files(): HasMany { return $this->hasMany(AgencyFile::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function metrics(): HasMany { return $this->hasMany(Metric::class); }
    public function reports(): HasMany { return $this->hasMany(Report::class); }
    public function approvals(): HasMany { return $this->hasMany(Approval::class); }
    public function contentCalendarItems(): HasMany { return $this->hasMany(ContentCalendar::class); }
    public function websiteProjects(): HasMany { return $this->hasMany(WebsiteProject::class); }
}
