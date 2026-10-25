<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'request_clock_in',
        'request_clock_out',
        'request_note',
        'status',
        'approved_at',
    ];

    protected $casts = [
        'request_clock_in' => 'datetime:H:i',
        'request_clock_out' => 'datetime:H:i',
        'approved_at' => 'datetime:H:i',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function restRequests()
    {
        return $this->hasMany(RequestRest::class);
    }
}
