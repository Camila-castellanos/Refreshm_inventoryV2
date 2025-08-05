<?php

namespace App\Models\Scopes;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use App\Models\User;

class CompanyUsersSharedScope implements Scope
{

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check()) {
            if (Auth::user()->company_id) {
                $companyId = Auth::user()->company_id;
                $userIds = User::where('company_id', $companyId)->pluck('id');
                $tableName = $model->getTable();
                $builder->whereIn("{$tableName}.user_id", $userIds);
            } else {
                // Si no tiene company_id, no devuelve ningún registro
                $builder->whereRaw('1 = 0');
            }
        }
    }
}