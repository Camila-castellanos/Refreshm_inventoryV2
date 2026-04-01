<?php

namespace Tests\Unit\Models;

use App\Models\MailList;
use Tests\TestCaseWithCompany;

class MailListModelTest extends TestCaseWithCompany
{
    public function test_mail_list_belongs_to_company(): void
    {
        $mailList = MailList::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->assertInstanceOf(\App\Models\Company::class, $mailList->company);
    }

    public function test_mail_list_belongs_to_user(): void
    {
        $mailList = MailList::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $this->assertInstanceOf(\App\Models\User::class, $mailList->user);
    }

    public function test_mail_list_has_company_scope(): void
    {
        $company2 = \App\Models\Company::factory()->create();

        MailList::factory()->create(['company_id' => $this->company->id]);
        MailList::factory()->create(['company_id' => $company2->id]);

        $mailLists = MailList::all();

        $this->assertGreaterThanOrEqual(1, $mailLists->count());
    }
}
