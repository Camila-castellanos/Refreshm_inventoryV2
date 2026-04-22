<?php

namespace App\Models\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CompanyUsersSharedScope implements Scope
{
    /**
     * Map table names to permission modules
     */
    protected $moduleMap = [
        'sales' => 'Accounting',
        'expenses' => 'Accounting',
        'bills' => 'Accounting',
        'taxes' => 'Accounting',
        'payments' => 'Accounting',
        'cash_on_hands' => 'Accounting',
        'drafts' => 'Accounting',
        'customers' => 'Contacts',
        'prospects' => 'Contacts',
        'vendors' => 'Contacts',
        'email_templates' => 'Contacts',
        'items' => 'Inventory',
        'incoming_requests' => 'Inventory',
        'incoming_request_items' => 'Inventory',
    ];

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->company_id) {
                $tableName = $model->getTable();
                $module = $this->moduleMap[$tableName] ?? null;

                $canViewAll = false;

                if ($user->role === 'OWNER') {
                    $canViewAll = true;
                } elseif ($module) {
                    $permissions = $user->page_permissions;
                    if (is_string($permissions)) {
                        $permissions = json_decode($permissions, true) ?? [];
                    }
                    if (is_array($permissions) && isset($permissions[$module])) {
                        $modulePerms = $permissions[$module];
                        if (is_array($modulePerms) && in_array('View Organization Data', $modulePerms)) {
                            $canViewAll = true;
                        }
                    }
                }

                if ($canViewAll) {
                    $userIds = User::where('company_id', $user->company_id)->pluck('id');
                    $builder->whereIn("{$tableName}.user_id", $userIds);
                } else {
                    $builder->where("{$tableName}.user_id", $user->id);
                }
            } else {
                // Si no tiene company_id, no devuelve ningún registro
                $builder->whereRaw('1 = 0');
            }
        }
    }
}
