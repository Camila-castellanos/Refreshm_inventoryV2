<?php

namespace App\Actions\Fortify;

use App\Models\Company;
use App\Models\Shop;
use App\Models\User;
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
        if ($input['invitation']) {
            Validator::make($input, [
                'name' => ['required', 'string', 'max:255'],
                'companyName' => ['string', 'max:255', 'exists:companies,name'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => $this->passwordRules(),
                'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
            ])->validate();

        } else {
            Validator::make($input, [
                'name' => ['required', 'string', 'max:255'],
                'companyName' => ['required', 'string', 'max:255', 'unique:companies,name'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => $this->passwordRules(),
                'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
            ])->validate();

        }

        $role = ! empty($input['invitation']) ? 'USER' : 'ADMIN';

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'role' => $role,
            'password' => Hash::make($input['password']),
        ]);

        if ($input['invitation']) {
            $company = Company::where('name', $input['companyName'])->first();
        } else {
            $company = Company::create([
                'name' => $input['companyName'],
                'owner_id' => $user->id,
            ]);

            $shop = Shop::create([
                'name' => 'Main Shop',
                'company_id' => $company->id,
            ]);

            \App\Models\Storage::create([
                'name' => 'Default Storage',
                'limit' => 100,
                'company_id' => $company->id,
                'priority' => 1,
                'is_default' => true,
            ]);
        }

        $user->company_id = $company->id;
        $user->save();

        return $user;
    }
}
