<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PerceptionType;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PerceptionTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PerceptionType');
    }

    public function view(AuthUser $authUser, PerceptionType $perceptionType): bool
    {
        return $authUser->can('View:PerceptionType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PerceptionType');
    }

    public function update(AuthUser $authUser, PerceptionType $perceptionType): bool
    {
        return $authUser->can('Update:PerceptionType');
    }

    public function delete(AuthUser $authUser, PerceptionType $perceptionType): bool
    {
        return $authUser->can('Delete:PerceptionType');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PerceptionType');
    }

    public function restore(AuthUser $authUser, PerceptionType $perceptionType): bool
    {
        return $authUser->can('Restore:PerceptionType');
    }

    public function forceDelete(AuthUser $authUser, PerceptionType $perceptionType): bool
    {
        return $authUser->can('ForceDelete:PerceptionType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PerceptionType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PerceptionType');
    }

    public function replicate(AuthUser $authUser, PerceptionType $perceptionType): bool
    {
        return $authUser->can('Replicate:PerceptionType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PerceptionType');
    }
}
