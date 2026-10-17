<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kajian extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'ustadz',
        'category',
        'audience',
        'masjid',
        'address',
        'lat',
        'lon',
        'day',
        'time',
        'description',
        'verified',
        'source',
        'source_url',
        'status',
        'alasan_batal',
        'is_rutin',
        'link_video',
        'poster_url',
        'user_id',
    ];

    protected $casts = [
        'lat' => 'float',
        'lon' => 'float',
        'verified' => 'boolean',
        'is_rutin' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
