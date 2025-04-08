<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Company;
use App\Models\Shop;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MigrateItemsToShopsKeepUserId extends Command
{
    protected $signature = 'migrate:items-to-shops-keep-user';
    protected $description = 'Creates Company/Shop for users and populates items.shop_id based on items.user_id, keeping user_id.';

    public function handle()
    {
        $this->info('Starting migration to populate items.shop_id...');

        DB::transaction(function () {
            // Find users needing migration (no company assigned yet)
            $usersToMigrate = User::whereNull('company_id')->get();

            if ($usersToMigrate->isEmpty()) {
                $this->info('No users found needing migration to a company.');
                return;
            }

            $this->info("Found {$usersToMigrate->count()} users to migrate.");
            $progressBar = $this->output->createProgressBar($usersToMigrate->count());
            $progressBar->start();

            foreach ($usersToMigrate as $user) {
                $this->line("\nProcessing User ID: {$user->id} ({$user->email})");

                // 1. Create Company (ensure unique name)
                $companyName = $this->generateUniqueCompanyName($user->name . " Company");
                $company = Company::create(['name' => $companyName, 'owner_id' => $user->id]);
                $this->info(" -> Created Company: '{$company->name}' (ID: {$company->id})");

                // 2. Assign User to Company
                $user->company_id = $company->id;
                $user->save();
                $this->info(" -> Assigned User ID {$user->id} to Company ID {$company->id}");

                // 3. Create Default Shop
                $shop = Shop::create(['company_id' => $company->id, 'name' => 'Main Shop']);
                $this->info(" -> Created default Shop: '{$shop->name}' (ID: {$shop->id})");

                // 4. Populate shop_id for items belonging to this user
                //    Only update items that haven't been assigned a shop yet.
                $itemsUpdatedCount = Item::where('user_id', $user->id)
                                         ->whereNull('shop_id')
                                         ->update(['shop_id' => $shop->id]);

                if ($itemsUpdatedCount > 0) {
                    $this->info(" -> Populated shop_id for {$itemsUpdatedCount} items based on user_id {$user->id}.");
                } else {
                    $this->info(" -> No items found needing shop_id populated for user_id {$user->id}.");
                }

                $progressBar->advance();
            } // End foreach user

            $progressBar->finish();
            $this->info("\nUser and item shop_id population completed.");

        }); // End DB Transaction

        $this->info('Item shop_id migration command finished!');
        return Command::SUCCESS;
    }

    private function generateUniqueCompanyName(string $baseName): string {
        $name = $baseName;
        $counter = 1;
        while (Company::where('name', $name)->exists()) {
            $name = $baseName . ' (' . $counter++ . ')';
            if ($counter > 100) { throw new \RuntimeException("Failed to generate unique company name for base: {$baseName}"); }
        }
        return $name;
    }
}