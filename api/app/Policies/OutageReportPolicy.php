<?php

namespace App\Policies;

use App\Models\OutageReport;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OutageReportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, OutageReport $outageReport): bool
    {
        return $user->id === $outageReport->user_id;
    }

    /**
     * Determine whether the user can close the model.
     */
    public function close(User $user, OutageReport $outageReport): bool
    {
        return $user->id === $outageReport->user_id;
    }
}
