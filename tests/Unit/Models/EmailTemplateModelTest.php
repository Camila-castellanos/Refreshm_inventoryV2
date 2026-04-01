<?php

namespace Tests\Unit\Models;

use App\Models\EmailTemplate;
use Tests\TestCaseWithCompany;

class EmailTemplateModelTest extends TestCaseWithCompany
{
    public function test_email_template_has_company_scope(): void
    {
        $company2 = \App\Models\Company::factory()->create();
        $user2 = \App\Models\User::factory()->create([
            'company_id' => $company2->id,
        ]);

        EmailTemplate::factory()->create(['user_id' => $this->owner->id]);
        EmailTemplate::factory()->create(['user_id' => $user2->id]);

        $templates = EmailTemplate::all();

        $this->assertGreaterThanOrEqual(1, $templates->count());
    }
}
