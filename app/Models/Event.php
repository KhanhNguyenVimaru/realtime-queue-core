<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory;

    protected $fillable = [
        'host_id',
        'title',
        'description',
        'img',
        'limit',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'limit' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_user')
            ->withPivot(['status'])
            ->withTimestamps();
    }

    public function eventUsers(): HasMany
    {
        return $this->hasMany(EventUser::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(EventLog::class);
    }
}
