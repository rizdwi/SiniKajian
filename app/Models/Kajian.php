<?php

namespace App\Models;

use Carbon\Carbon;
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
        'event_datetime',
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
        'event_datetime' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($kajian) {
            if (!$kajian->event_datetime && $kajian->day && $kajian->time) {
                $kajian->event_datetime = static::parseDateTime($kajian->day, $kajian->time);
            }
        });
    }

    /**
     * Hitung Carbon datetime dari nama hari dan teks jam kajian.
     */
    public static function parseDateTime(string $day, ?string $time, $base = null): Carbon
    {
        $baseDate = $base ? Carbon::parse($base)->startOfDay() : Carbon::now()->startOfDay();
        $daysMap = [
            'senin' => 1, 'selasa' => 2, 'rabu' => 3, 'kamis' => 4,
            'jumat' => 5, 'sabtu' => 6, 'ahad' => 7, 'minggu' => 7
        ];
        $targetDay = strtolower(trim($day));
        if (isset($daysMap[$targetDay])) {
            $diff = $daysMap[$targetDay] - $baseDate->dayOfWeekIso;
            if ($diff < 0) {
                $diff += 7;
            }
            $eventDate = $baseDate->copy()->addDays($diff);
        } else {
            $eventDate = $baseDate->copy();
        }

        $hour = 9;
        $min = 0;
        $timeStr = (string)$time;
        if (preg_match('/(\d{1,2})[:.](\d{2})/', $timeStr, $m)) {
            $hour = (int)$m[1];
            $min = (int)$m[2];
        } elseif (stripos($timeStr, 'subuh') !== false || stripos($timeStr, 'fajar') !== false) {
            $hour = 5;
            $min = 30;
        } elseif (stripos($timeStr, 'dzuhur') !== false) {
            $hour = 12;
            $min = 15;
        } elseif (stripos($timeStr, 'ashar') !== false) {
            $hour = 15;
            $min = 30;
        } elseif (stripos($timeStr, 'maghrib') !== false) {
            $hour = 18;
            $min = 15;
        } elseif (stripos($timeStr, 'isya') !== false) {
            $hour = 19;
            $min = 30;
        }

        return $eventDate->setTime($hour, $min);
    }

    /**
     * Otomatis hapus kajian dari database jika jam kajian sudah lewat 3-6 jam.
     */
    public static function purgeExpiredKajians(int $hours = 4): int
    {
        $cutoff = Carbon::now()->subHours($hours);

        // Rutin: majukan jadwal +7 hari jika sudah lewat
        $rutinExpired = self::where('is_rutin', true)
            ->whereNotNull('event_datetime')
            ->where('event_datetime', '<', $cutoff)
            ->get();
        foreach ($rutinExpired as $k) {
            $k->event_datetime = Carbon::parse($k->event_datetime)->addDays(7);
            $k->save();
        }

        // Non-rutin: otomatis terhapus dari database agar user tidak bingung
        return self::where('is_rutin', false)
            ->whereNotNull('event_datetime')
            ->where('event_datetime', '<', $cutoff)
            ->delete();
    }

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
