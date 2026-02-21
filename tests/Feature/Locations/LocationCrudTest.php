<?php

namespace Tests\Feature\Locations;

use Tests\TestCaseWithCompany;

class LocationCrudTest extends TestCaseWithCompany
{
    public function test_locations_require_authentication(): void
    {
        $response = $this->get('/stores/1/locations');

        $response->assertRedirect('/login');
    }
}
