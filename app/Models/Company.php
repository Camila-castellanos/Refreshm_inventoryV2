<?php

namespace App\Models;

// Import necessary classes
use Illuminate\Database\Eloquent\Factories\HasFactory; // For seeding/testing
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // For the owner relationship
use Illuminate\Database\Eloquent\Relations\HasMany; // For users and shops relationships
use Illuminate\Database\Eloquent\Relations\HasManyThrough; // For items relationship

class Company extends Model
{
    // Use HasFactory trait (good practice for testing/seeding)
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * Define which columns can be filled using Company::create([...]) or $company->fill([...])
     * We need 'name' and 'owner_id' based on how we create companies in the data migration command.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'owner_id',
        // Add other company fields here if they should be mass assignable
        // 'address', 'phone', 'vat_number',
    ];

    /**
     * Define the relationship: A Company BELONGS TO one owner (User).
     * Eloquent assumes the foreign key is owner_id based on the method name ('owner' + '_id').
     * We specify 'owner_id' explicitly for clarity and safety.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Define the relationship: A Company HAS MANY Users.
     * Eloquent assumes the foreign key on the 'users' table is 'company_id'
     * (based on the model name 'Company' converted to snake_case + '_id').
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'company_id');
    }

    /**
     * Define the relationship: A Company HAS MANY Shops.
     * Eloquent assumes the foreign key on the 'shops' table is 'company_id'.
     */
    public function shops(): HasMany
    {
        return $this->hasMany(Shop::class, 'company_id');
    }

    /**
     * Define a "Has Many Through" relationship: A Company HAS MANY Items THROUGH its Shops.
     * This is a convenient way to get all items belonging to any shop within this company.
     * Laravel figures out the intermediate keys:
     * - It looks for 'company_id' on the 'shops' table (the intermediate model).
     * - It looks for 'shop_id' on the 'items' table (the final model).
     */
    public function items(): HasManyThrough
    {
        // Structure: hasManyThrough(FinalModel::class, IntermediateModel::class)
        return $this->hasManyThrough(Item::class, Shop::class);
    }
}