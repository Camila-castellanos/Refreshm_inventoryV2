<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Models\Company;
use App\Models\Shop;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {       
        if($input['invitation']) {
            Validator::make($input, [
                'name' => ['required', 'string', 'max:255'],
                "companyName" => ['string', 'max:255', 'exists:companies,name'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => $this->passwordRules(),
                'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
            ])->validate();
    
        } else {
            Validator::make($input, [
                'name' => ['required', 'string', 'max:255'],
                "companyName" => ['required', 'string', 'max:255', 'unique:companies,name'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => $this->passwordRules(),
                'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
            ])->validate();
    
        }

      
        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'role' => "USER", // Default role
            'password' => Hash::make($input['password']),
        ]);

        if ($input['invitation']) {
            $company = Company::where("name", $input["companyName"])->first();
        } else {
            $company = Company::create([
                'name' => $input['companyName'],
                'owner_id' => $user->id,
            ]);

            $shop = Shop::create([
                'name' => 'Main Shop',
                'company_id' => $company->id,
            ]);
        }

        
     
        $user->company_id = $company->id; 
        $user->save();  

        return $user;
    }
}
