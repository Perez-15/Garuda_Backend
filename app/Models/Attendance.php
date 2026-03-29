<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'time_in',
        'time_out',
        'latitude',
        'longitude',
        'status',
    ];

    protected $casts = [
        'date'      => 'date',
        'latitude'  => 'float',
        'longitude' => 'float',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Determines attendance status based on time_in vs the late cutoff.
     * Cutoff is configurable via ATTENDANCE_LATE_CUTOFF in .env (default: 09:00).
     */
    public static function resolveStatus(string $timeIn): string
    {
        $cutoff = config('attendance.late_cutoff', '08:15:00');

        return $timeIn <= $cutoff ? 'Present' : 'Late';
    }

    /**
     * Haversine distance in meters between two GPS points.
     * Used server-side as a security check (cannot be spoofed via JS).
     */
    public static function haversineDistance(
        float $lat1, float $lon1,
        float $lat2, float $lon2
    ): float {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}