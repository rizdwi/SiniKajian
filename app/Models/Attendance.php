<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_token',
        'kajian_id',
        'attendance_date',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kajian(): BelongsTo
    {
        return $this->belongsTo(Kajian::class);
    }
}
