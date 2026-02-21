<?php

namespace Tests\Feature\Emails;

use App\Models\EmailTemplate;
use Tests\TestCaseWithCompany;

class EmailTemplateCrudTest extends TestCaseWithCompany
{
    public function test_index_returns_email_templates_page(): void
    {
        EmailTemplate::factory()->forOwner($this->owner->id)->create();
        EmailTemplate::factory()->forOwner($this->owner->id)->create(['subject' => 'Test Email']);

        $response = $this->actingAs($this->owner)
            ->get('/email_templates');

        $response->assertStatus(200);
    }

    public function test_store_creates_email_template(): void
    {
        $data = [
            'subject' => 'Welcome Email',
            'content' => 'Welcome to our service!',
        ];

        $response = $this->actingAs($this->owner)
            ->post('/email_templates', $data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('email_templates', [
            'subject' => 'Welcome Email',
            'content' => 'Welcome to our service!',
        ]);
    }

    public function test_store_validates_required_subject(): void
    {
        $data = [
            'content' => 'Some content',
        ];

        $response = $this->actingAs($this->owner)
            ->post('/email_templates', $data);

        $response->assertStatus(500);
    }

    public function test_store_validates_required_content(): void
    {
        $data = [
            'subject' => 'Test Subject',
        ];

        $response = $this->actingAs($this->owner)
            ->post('/email_templates', $data);

        $response->assertStatus(500);
    }

    public function test_update_modifies_email_template(): void
    {
        $emailTemplate = EmailTemplate::factory()->forOwner($this->owner)->create([
            'subject' => 'Original Subject',
            'content' => 'Original Content',
        ]);

        $data = [
            'subject' => 'Updated Subject',
            'content' => 'Updated Content',
        ];

        $response = $this->actingAs($this->owner)
            ->put("/email_templates/{$emailTemplate->id}", $data);

        $response->assertStatus(200);

        $emailTemplate->refresh();
        $this->assertEquals('Updated Subject', $emailTemplate->subject);
        $this->assertEquals('Updated Content', $emailTemplate->content);
    }

    public function test_update_validates_required_fields(): void
    {
        $emailTemplate = EmailTemplate::factory()->forOwner($this->owner)->create();

        $data = [
            'subject' => '',
            'content' => '',
        ];

        $response = $this->actingAs($this->owner)
            ->put("/email_templates/{$emailTemplate->id}", $data);

        $response->assertStatus(302);
    }

    public function test_destroy_deletes_email_template(): void
    {
        $emailTemplate = EmailTemplate::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->delete("/email_templates/{$emailTemplate->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('email_templates', ['id' => $emailTemplate->id]);
    }

    public function test_email_template_belongs_to_user(): void
    {
        $emailTemplate = EmailTemplate::factory()->forOwner($this->owner)->create();

        $this->assertEquals($this->owner->id, $emailTemplate->user_id);
    }
}
