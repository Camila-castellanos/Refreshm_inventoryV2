<?php

namespace App\Models\Scopes;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use App\Models\Storage;
use App\Models\User;

class CompanyItemScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check() && Auth::user()->company_id) {
            $companyId = Auth::user()->company_id;
            $storageIds = \App\Models\Storage::where('company_id', $companyId)->pluck('id');
            $userIds = \App\Models\User::where('company_id', $companyId)->pluck('id');

            $builder->where(function($q) use ($storageIds, $userIds) {
                $q->whereIn('items.storage_id', $storageIds)
                  ->orWhereIn('items.user_id', $userIds);
            });
        }
    }
}