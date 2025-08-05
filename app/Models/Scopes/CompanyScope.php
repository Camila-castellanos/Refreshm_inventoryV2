<?php

namespace App\Models\Scopes;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CompanyScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
   public function apply(Builder $builder, Model $model): void
{
    if (Auth::check()) {
        $tableName = $model->getTable();
        if (Auth::user()->company_id) {
            $builder->where("{$tableName}.company_id", Auth::user()->company_id);
        } else {
            // Si no tiene company_id, no devuelve ningún registro
            $builder->whereRaw('1 = 0');
        }
    }
}
}