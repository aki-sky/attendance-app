<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'rest_start',
        'rest_end',
    ];

    protected $casts = [
        'rest_start' => 'datetime:H:i',
        'rest_end' => 'datetime:H:i',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
