<?php

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\Prospect;
use Tests\TestCaseWithCompany;

class ContactModelTest extends TestCaseWithCompany
{
    public function test_contact_delete_contacts_by_prospect_returns_count(): void
    {
        $prospect = Prospect::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        Contact::factory()->create([
            'prospect_id' => $prospect->id,
            'type' => 'prospect',
        ]);
        Contact::factory()->create([
            'prospect_id' => $prospect->id,
            'type' => 'prospect',
        ]);

        $deleted = Contact::deleteContactsByProspect($prospect->id);

        $this->assertEquals(2, $deleted);
        $this->assertEquals(0, Contact::where('prospect_id', $prospect->id)->count());
    }

    public function test_contact_delete_contacts_by_customer_returns_count(): void
    {
        $customer = Customer::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        Contact::factory()->create([
            'customer_id' => $customer->id,
            'type' => 'customer',
        ]);
        Contact::factory()->create([
            'customer_id' => $customer->id,
            'type' => 'customer',
        ]);
        Contact::factory()->create([
            'customer_id' => $customer->id,
            'type' => 'customer',
        ]);

        $deleted = Contact::deleteContactsByCustomer($customer->id);

        $this->assertEquals(3, $deleted);
        $this->assertEquals(0, Contact::where('customer_id', $customer->id)->count());
    }

    public function test_contact_delete_contacts_by_prospect_ignores_customer_contacts(): void
    {
        $prospect = Prospect::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        Contact::factory()->create([
            'prospect_id' => $prospect->id,
            'type' => 'prospect',
        ]);
        Contact::factory()->create([
            'prospect_id' => $prospect->id,
            'type' => 'customer',
        ]);

        $deleted = Contact::deleteContactsByProspect($prospect->id);

        $this->assertEquals(1, $deleted);
        $this->assertEquals(1, Contact::where('prospect_id', $prospect->id)->count());
    }

    public function test_contact_delete_contacts_by_customer_ignores_prospect_contacts(): void
    {
        $customer = Customer::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        Contact::factory()->create([
            'customer_id' => $customer->id,
            'type' => 'customer',
        ]);
        Contact::factory()->create([
            'customer_id' => $customer->id,
            'type' => 'prospect',
        ]);

        $deleted = Contact::deleteContactsByCustomer($customer->id);

        $this->assertEquals(1, $deleted);
        $this->assertEquals(1, Contact::where('customer_id', $customer->id)->count());
    }
}
