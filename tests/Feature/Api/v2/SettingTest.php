<?php

namespace Tests\Feature\Api\v2;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_get_all_settings()
    {
        Setting::create([
            'key' => 'site_name',
            'value' => 'APlus Learning',
            'group' => 'general',
            'type' => 'text',
            'is_locked' => false,
        ]);

        Setting::create([
            'key' => 'contact_email',
            'value' => 'support@aplus.com',
            'group' => 'contact',
            'type' => 'text',
            'is_locked' => false,
        ]);

        $response = $this->getJson('/api/v2/settings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'settings' => [
                        '*' => ['key', 'value', 'group', 'type'],
                    ],
                ],
            ])
            ->assertJson([
                'status' => 200,
                'message' => 'Settings retrieved successfully',
            ]);

        $this->assertCount(2, $response->json('data.settings'));
    }

    /** @test */
    public function it_can_filter_settings_by_group()
    {
        Setting::create([
            'key' => 'site_name',
            'value' => 'APlus Learning',
            'group' => 'general',
            'type' => 'text',
            'is_locked' => false,
        ]);

        Setting::create([
            'key' => 'contact_email',
            'value' => 'support@aplus.com',
            'group' => 'contact',
            'type' => 'text',
            'is_locked' => false,
        ]);

        $response = $this->getJson('/api/v2/settings?group=general');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 200,
                'data' => [
                    'settings' => [
                        [
                            'key' => 'site_name',
                            'value' => 'APlus Learning',
                            'group' => 'general',
                        ],
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('data.settings'));
    }

    /** @test */
    public function it_excludes_locked_settings_from_public_api()
    {
        Setting::create([
            'key' => 'public_setting',
            'value' => 'Public Value',
            'group' => 'general',
            'type' => 'text',
            'is_locked' => false,
        ]);

        Setting::create([
            'key' => 'secret_setting',
            'value' => 'Secret Value',
            'group' => 'general',
            'type' => 'text',
            'is_locked' => true,
        ]);

        $response = $this->getJson('/api/v2/settings');

        $response->assertStatus(200);

        $settings = $response->json('data.settings');
        $keys = array_column($settings, 'key');

        $this->assertContains('public_setting', $keys);
        $this->assertNotContains('secret_setting', $keys);
    }

    /** @test */
    public function it_can_get_specific_setting_by_key()
    {
        Setting::create([
            'key' => 'site_name',
            'value' => 'APlus Learning',
            'group' => 'general',
            'type' => 'text',
            'is_locked' => false,
        ]);

        $response = $this->getJson('/api/v2/settings/site_name');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 200,
                'message' => 'Setting retrieved successfully',
                'data' => [
                    'setting' => [
                        'key' => 'site_name',
                        'value' => 'APlus Learning',
                        'group' => 'general',
                        'type' => 'text',
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_returns_404_for_non_existent_setting()
    {
        $response = $this->getJson('/api/v2/settings/non_existent_key');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 404,
                'message' => 'Setting not found',
            ]);
    }

    /** @test */
    public function it_returns_404_for_locked_setting()
    {
        Setting::create([
            'key' => 'secret_setting',
            'value' => 'Secret Value',
            'group' => 'general',
            'type' => 'text',
            'is_locked' => true,
        ]);

        $response = $this->getJson('/api/v2/settings/secret_setting');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 404,
                'message' => 'Setting not found',
            ]);
    }

    /** @test */
    public function it_can_get_all_setting_groups()
    {
        Setting::create([
            'key' => 'site_name',
            'value' => 'APlus',
            'group' => 'general',
            'type' => 'text',
            'is_locked' => false,
        ]);

        Setting::create([
            'key' => 'contact_email',
            'value' => 'support@aplus.com',
            'group' => 'contact',
            'type' => 'text',
            'is_locked' => false,
        ]);

        Setting::create([
            'key' => 'secondary_email',
            'value' => 'info@aplus.com',
            'group' => 'contact',
            'type' => 'text',
            'is_locked' => false,
        ]);

        $response = $this->getJson('/api/v2/settings/groups');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 200,
                'message' => 'Setting groups retrieved successfully',
            ]);

        $this->assertEqualsCanonicalizing(['general', 'contact'], $response->json('data.groups'));
    }
}
