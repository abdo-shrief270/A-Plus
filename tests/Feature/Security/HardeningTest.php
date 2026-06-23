<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\EnforceSingleDevice;
use App\Http\Middleware\JwtMiddleware;
use App\Services\Auth\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\BuildsQuizWorld;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;
    use BuildsQuizWorld;

    public function test_student_cannot_read_another_students_profile(): void
    {
        $this->withoutMiddleware([JwtMiddleware::class, EnforceSingleDevice::class]);

        $a = $this->makeStudent();
        $b = $this->makeStudent();

        // Own profile is accessible…
        $this->actingAs($a->user, 'api')
            ->getJson("/api/v2/students/{$a->id}")
            ->assertOk();

        // …another student's is not (IDOR closed).
        $this->actingAs($a->user, 'api')
            ->getJson("/api/v2/students/{$b->id}")
            ->assertStatus(404);
    }

    public function test_student_cannot_update_another_students_profile(): void
    {
        $this->withoutMiddleware([JwtMiddleware::class, EnforceSingleDevice::class]);

        $a = $this->makeStudent();
        $b = $this->makeStudent();
        $originalEmail = $b->user->email;

        $this->actingAs($a->user, 'api')
            ->putJson("/api/v2/students/{$b->id}", ['name' => 'Hacked', 'email' => 'attacker@evil.com'])
            ->assertStatus(404);

        $this->assertSame($originalEmail, $b->user->fresh()->email, 'victim email must be unchanged');
    }

    public function test_otp_code_is_random_not_a_constant(): void
    {
        $svc = app(OtpService::class);
        $method = new \ReflectionMethod($svc, 'generateOtpCode');
        $method->setAccessible(true);

        $codes = collect(range(1, 25))->map(fn () => $method->invoke($svc));

        $this->assertGreaterThan(1, $codes->unique()->count(), 'OTP must be random, not a hardcoded constant');
        $codes->each(fn ($c) => $this->assertMatchesRegularExpression('/^\d{4,8}$/', $c));
    }
}
