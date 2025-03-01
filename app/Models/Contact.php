<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "email",
        "type",
        "user_id",
        "prospect_id",
        "customer_id"
    ];

    /**
     * Delete contacts by prospect ID.
     *
     * @param int $prospect_id
     * @return int Number of deleted records
     */
    public static function deleteContactsByProspect($prospect_id)
    {
        return self::where('prospect_id', $prospect_id)->where('type', 'prospect')->delete();
    }

    /**
     * Delete contacts by customer ID.
     *
     * @param int $customer_id
     * @return int Number of deleted records
     */
    public static function deleteContactsByCustomer($customer_id)
    {
        return self::where('customer_id', $customer_id)->where('type', 'customer')->delete();
    }
}
