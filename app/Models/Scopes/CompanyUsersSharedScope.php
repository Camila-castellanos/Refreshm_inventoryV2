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
        if (Auth::check() && Auth::user()->company_id) {
            $companyId = Auth::user()->company_id;
            
            // Obtener todos los user_ids de la compañía del usuario actual
            $userIds = User::where('company_id', $companyId)->pluck('id');
            
            // Obtener el nombre de la tabla del modelo actual
            $tableName = $model->getTable();
            
            // Aplicar el filtro usando el nombre de la tabla
            $builder->whereIn("{$tableName}.user_id", $userIds);
        }
    }
}