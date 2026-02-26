<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CompanyItemScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check() && Auth::user()->company_id) {
            $user = Auth::user();
            $companyId = $user->company_id;

            $canViewAll = false;
            if ($user->role === 'OWNER') {
                $canViewAll = true;
            } else {
                $permissions = $user->page_permissions;
                if (is_string($permissions)) {
                    $permissions = json_decode($permissions, true) ?? [];
                }
                if (is_array($permissions) && isset($permissions['Inventory'])) {
                    $inventoryPerms = $permissions['Inventory'];
                    if (is_array($inventoryPerms) && in_array('View Organization Data', $inventoryPerms)) {
                        $canViewAll = true;
                    }
                }
            }

            if ($canViewAll) {
                $storageIds = \App\Models\Storage::where('company_id', $companyId)->pluck('id');
                $userIds = \App\Models\User::where('company_id', $companyId)->pluck('id');

                $builder->where(function ($q) use ($storageIds, $userIds) {
                    $q->whereIn('items.storage_id', $storageIds)
                        ->orWhereIn('items.user_id', $userIds);
                });
            } else {
                $builder->where('items.user_id', $user->id);
            }
        }
    }
}
