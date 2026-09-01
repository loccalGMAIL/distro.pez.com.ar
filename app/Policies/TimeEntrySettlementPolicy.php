<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TimeEntrySettlement;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class TimeEntrySettlementPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TimeEntrySettlement');
    }

    public function view(AuthUser $authUser, TimeEntrySettlement $timeEntrySettlement): bool
    {
        return $authUser->can('View:TimeEntrySettlement');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TimeEntrySettlement');
    }

    public function update(AuthUser $authUser, TimeEntrySettlement $timeEntrySettlement): bool
    {
        return $authUser->can('Update:TimeEntrySettlement');
    }

    public function delete(AuthUser $authUser, TimeEntrySettlement $timeEntrySettlement): bool
    {
        return $authUser->can('Delete:TimeEntrySettlement');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:TimeEntrySettlement');
    }

    public function restore(AuthUser $authUser, TimeEntrySettlement $timeEntrySettlement): bool
    {
        return $authUser->can('Restore:TimeEntrySettlement');
    }

    public function forceDelete(AuthUser $authUser, TimeEntrySettlement $timeEntrySettlement): bool
    {
        return $authUser->can('ForceDelete:TimeEntrySettlement');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TimeEntrySettlement');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TimeEntrySettlement');
    }

    public function replicate(AuthUser $authUser, TimeEntrySettlement $timeEntrySettlement): bool
    {
        return $authUser->can('Replicate:TimeEntrySettlement');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TimeEntrySettlement');
    }
}
