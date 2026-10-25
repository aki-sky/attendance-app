<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendanceRecordPolicy
{
    /**
     * 勤怠の更新権限
     */
    public function update(User $user, Attendance $attendance): bool
    {
        return $user->id === $attendance->user_id
            || $user->role === 1;
    }

    /**
     * 勤怠の削除権限
     */
    public function delete(User $user, Attendance $attendance): bool
    {
        return $user->id === $attendance->user_id
            || $user->role === 1;
    }
}
