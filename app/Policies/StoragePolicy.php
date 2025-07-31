<?php

namespace App\Policies;

use App\Models\Storage;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StoragePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Los usuarios pueden ver storages si pertenecen a la misma compañía
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Storage $storage): bool
    {
        // Los usuarios pueden ver un storage si pertenecen a la misma compañía
        return $user->company_id === $storage->company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Los usuarios pueden crear storages para su propia compañía
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Storage $storage): bool
    {
        // Los usuarios pueden actualizar un storage si pertenecen a la misma compañía
        return $user->company_id === $storage->company_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Storage $storage): bool
    {
        // Los usuarios pueden eliminar un storage si pertenecen a la misma compañía
        return $user->company_id === $storage->company_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Storage $storage): bool
    {
        // Los usuarios pueden restaurar un storage si pertenecen a la misma compañía
        return $user->company_id === $storage->company_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Storage $storage): bool
    {
        // Los usuarios pueden eliminar permanentemente un storage si pertenecen a la misma compañía
        return $user->company_id === $storage->company_id;
    }
}
