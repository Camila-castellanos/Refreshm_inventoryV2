<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FixUserRolesAndPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-user-roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes user roles and page permissions for users created before the updates.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $newPermissions = config('permissions.defaults');

        // 1. Fix Company Owner Roles (USER -> ADMIN)
        $ownerIds = \App\Models\Company::pluck('owner_id')->filter();
        $updatedRoles = \App\Models\User::whereIn('id', $ownerIds)
            ->where('role', 'USER')
            ->update(['role' => 'ADMIN']);

        // 2. Fix Global Page Permissions
        // We match exactly the old default string to avoid overwriting custom permissions
        $oldPermsJson = '{"Inventory":["Active Inventory","On Hold","Sold"]}';

        $updatedPerms = \App\Models\User::where('page_permissions', $oldPermsJson)
            ->orWhereNull('page_permissions')
            ->orWhere(function ($query) {
                $query->where('role', 'ADMIN')
                    ->where(function ($q) {
                        $q->where('page_permissions', 'NOT LIKE', '%Accounting%')
                            ->orWhere('page_permissions', 'NOT LIKE', '%Contacts%');
                    });
            })
            ->update(['page_permissions' => $newPermissions]);

        // 3. Fix Missing Storages for Companies
        $companies = \App\Models\Company::all();
        $createdStorages = 0;
        foreach ($companies as $company) {
            $hasStorage = \App\Models\Storage::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->exists();

            if (! $hasStorage) {
                \App\Models\Storage::create([
                    'name' => 'Default Storage',
                    'limit' => 100,
                    'company_id' => $company->id,
                    'priority' => 1,
                    'is_default' => true,
                ]);
                $createdStorages++;
            }
        }

        $this->info("Success: {$updatedRoles} owner roles updated to ADMIN, {$updatedPerms} user permissions corrected, and {$createdStorages} default storages created.");
    }
}
