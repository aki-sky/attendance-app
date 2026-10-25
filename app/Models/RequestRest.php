<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestRest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_attendance_id',
        'request_rest_start',
        'request_rest_end',
    ];

    protected $casts = [
        'request_rest_start' => 'datetime:H:i',
        'request_rest_end' => 'datetime:H:i',
    ];

    public function requestAttendance()
    {
        return $this->belongsTo(RequestAttendance::class);
    }
}
