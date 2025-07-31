<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'store_id',
        'location_id',
        'role',
        'headers',
        'sold_headers',
        'company_id',
        'printable_tag_fields',
        'printable_invoice_fields',
        'timezone',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'printable_tag_fields' => 'array',
        'printable_invoice_fields' => 'array',
    ];

     protected $attributes = [
        'printable_tag_fields' => '["manufacturer","model","storage","colour","battery","imei"]',
        "printable_invoice_fields" => '["logo",
            "header",
            "billing_address",
            "invoice_number",
            "invoice_due",
            "payment_due",
            "amount_due",
            "items",
            "table_device",
            "table_issues",
            "table_imei",
            "table_price",
            "subtotal",
            "tax",
            "total",
            "credit",
            "footer"]',
            'role' => 'USER',
        ];

    protected static function booted()
    {
        static::created(function ($user) {
            // Crear el storage predeterminado para el usuario
            $user->storages()->create([
                'name' => 'Default Location',
                'limit' => 50, // Ajusta el límite predeterminado según sea necesario
            ]);
        });
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
    ];


    /**
     * A User may belong to one Store.
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function stores(){
        return $this->hasMany(Store::class, 'user_id');
    }

        // ---- NEW RELATIONSHIPS ----

    /**
     * Define the relationship: A User BELONGS TO one Company.
     * Eloquent assumes the foreign key is 'company_id' on this User model's table.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

      /**
     * Define the relationship: A User might own one Company.
     * This looks for the 'owner_id' column on the 'companies' table.
     * Use HasOne if a user can only own one company.
     */
    public function ownedCompany(): HasOne
    {
        return $this->hasOne(Company::class, 'owner_id');
        // If a user could own multiple companies, you would use:
        // return $this->hasMany(Company::class, 'owner_id');
    }

    /**
     * A User may belong to one Location.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    

public function drafts()
{
    return $this->hasMany(Draft::class, 'user_id');
}

}