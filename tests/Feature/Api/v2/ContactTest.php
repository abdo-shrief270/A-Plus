<?php

namespace Tests\Feature\Api\v2;

use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_submit_contact_form()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'description' => 'This is a test message with sufficient length.',
        ];

        $response = $this->postJson('/api/v2/contact', $data);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'contact' => [
                        'id',
                        'name',
                        'email',
                        'description',
                        'created_at',
                    ],
                ],
            ])
            ->assertJson([
                'status' => 201,
                'message' => 'Contact message submitted successfully',
                'data' => [
                    'contact' => [
                        'name' => 'John Doe',
                        'email' => 'john@example.com',
                        'description' => 'This is a test message with sufficient length.',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('contacts', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'description' => 'This is a test message with sufficient length.',
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->postJson('/api/v2/contact', []);

        $response->assertStatus(200)
            ->assertJson(['status' => 422])
            ->assertJsonValidationErrors(['name', 'email', 'description']);
    }

    /** @test */
    public function it_validates_email_format()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'description' => 'This is a test message.',
        ];

        $response = $this->postJson('/api/v2/contact', $data);

        $response->assertStatus(200)
            ->assertJson(['status' => 422])
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_validates_description_minimum_length()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'description' => 'Short',
        ];

        $response = $this->postJson('/api/v2/contact', $data);

        $response->assertStatus(200)
            ->assertJson(['status' => 422])
            ->assertJsonValidationErrors(['description']);
    }

    /** @test */
    public function it_validates_name_maximum_length()
    {
        $data = [
            'name' => str_repeat('a', 256),
            'email' => 'john@example.com',
            'description' => 'This is a test message with sufficient length.',
        ];

        $response = $this->postJson('/api/v2/contact', $data);

        $response->assertStatus(200)
            ->assertJson(['status' => 422])
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_validates_description_maximum_length()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'description' => str_repeat('a', 5001),
        ];

        $response = $this->postJson('/api/v2/contact', $data);

        $response->assertStatus(200)
            ->assertJson(['status' => 422])
            ->assertJsonValidationErrors(['description']);
    }
}
