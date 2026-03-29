<?php

namespace Tests\Feature\Emails;

use App\Models\EmailTemplate;
use Tests\TestCaseWithCompany;

class EmailTemplateCrudTest extends TestCaseWithCompany
{
    protected function createEmailTemplate(array $overrides = []): EmailTemplate
    {
        return EmailTemplate::factory()->forOwner($this->owner)->create($overrides);
    }

    public function test_index_returns_email_templates_page(): void
    {
        $this->createEmailTemplate(['subject' => 'Template 1']);
        $this->createEmailTemplate(['subject' => 'Template 2']);

        $response = $this->actingAs($this->owner)
            ->get('/email_templates');

        $response->assertStatus(200);
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->get('/email_templates');

        $response->assertRedirect('/login');
    }

    public function test_store_creates_email_template(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/email_templates', [
                'subject' => 'Welcome Email',
                'content' => 'Welcome to our service!',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('email_templates', [
            'subject' => 'Welcome Email',
            'content' => 'Welcome to our service!',
        ]);
    }

    public function test_store_without_required_fields_returns_500(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/email_templates', [
                'content' => 'Some content',
            ]);

        $response->assertStatus(500);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->post('/email_templates', [
            'subject' => 'Test',
            'content' => 'Content',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_update_modifies_email_template(): void
    {
        $template = $this->createEmailTemplate([
            'subject' => 'Original Subject',
            'content' => 'Original Content',
        ]);

        $response = $this->actingAs($this->owner)
            ->put("/email_templates/{$template->id}", [
                'subject' => 'Updated Subject',
                'content' => 'Updated Content',
            ]);

        $response->assertStatus(200);

        $template->refresh();
        $this->assertEquals('Updated Subject', $template->subject);
        $this->assertEquals('Updated Content', $template->content);
    }

    public function test_update_returns_404_for_nonexistent_template(): void
    {
        $response = $this->actingAs($this->owner)
            ->put('/email_templates/99999', [
                'subject' => 'Updated',
                'content' => 'Content',
            ]);

        $response->assertStatus(404);
    }

    public function test_update_validates_required_fields(): void
    {
        $template = $this->createEmailTemplate();

        $response = $this->actingAs($this->owner)
            ->put("/email_templates/{$template->id}", [
                'subject' => '',
                'content' => '',
            ]);

        $response->assertStatus(302);
    }

    public function test_update_requires_authentication(): void
    {
        $template = $this->createEmailTemplate();

        $response = $this->put("/email_templates/{$template->id}", [
            'subject' => 'Updated',
            'content' => 'Content',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_destroy_deletes_email_template(): void
    {
        $template = $this->createEmailTemplate();

        $response = $this->actingAs($this->owner)
            ->delete("/email_templates/{$template->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('email_templates', ['id' => $template->id]);
    }

    public function test_destroy_nonexistent_template_returns_500(): void
    {
        $response = $this->actingAs($this->owner)
            ->delete('/email_templates/99999');

        $response->assertStatus(500);
    }

    public function test_destroy_requires_authentication(): void
    {
        $template = $this->createEmailTemplate();

        $response = $this->delete("/email_templates/{$template->id}");

        $response->assertRedirect('/login');
    }

    public function test_email_template_belongs_to_user(): void
    {
        $template = $this->createEmailTemplate();

        $this->assertEquals($this->owner->id, $template->user_id);
    }

    public function test_store_with_special_characters_in_content(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/email_templates', [
                'subject' => 'Test Subject',
                'content' => 'Content with <html> and "quotes" and \'apostrophes\'',
            ]);

        $response->assertStatus(200);
    }

    public function test_update_with_html_content(): void
    {
        $template = $this->createEmailTemplate();

        $response = $this->actingAs($this->owner)
            ->put("/email_templates/{$template->id}", [
                'subject' => 'HTML Email',
                'content' => '<html><body><h1>Hello</h1></body></html>',
            ]);

        $response->assertStatus(200);
    }
}
