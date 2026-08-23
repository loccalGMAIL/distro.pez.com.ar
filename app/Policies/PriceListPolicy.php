<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PriceList;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PriceListPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PriceList');
    }

    public function view(AuthUser $authUser, PriceList $priceList): bool
    {
        return $authUser->can('View:PriceList');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PriceList');
    }

    public function update(AuthUser $authUser, PriceList $priceList): bool
    {
        return $authUser->can('Update:PriceList');
    }

    public function delete(AuthUser $authUser, PriceList $priceList): bool
    {
        return $authUser->can('Delete:PriceList');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PriceList');
    }

    public function restore(AuthUser $authUser, PriceList $priceList): bool
    {
        return $authUser->can('Restore:PriceList');
    }

    public function forceDelete(AuthUser $authUser, PriceList $priceList): bool
    {
        return $authUser->can('ForceDelete:PriceList');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PriceList');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PriceList');
    }

    public function replicate(AuthUser $authUser, PriceList $priceList): bool
    {
        return $authUser->can('Replicate:PriceList');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PriceList');
    }
}
