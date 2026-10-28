<?php

namespace Tests\Feature;

use App\Models\Offense;
use App\Models\Subject;
use App\Services\RiskScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskScoreApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_offense_weights_recalculate_subject_risk_and_classification(): void
    {
        $subject = Subject::factory()->create(['risk_score' => 15, 'risk_override' => false]);
        Offense::factory()->create(['subject_id' => $subject->id, 'category' => 'burglary']);
        app(RiskScoreService::class)->recalculate($subject);
        $this->assertSame(35, $subject->fresh()->risk_score);
        $this->assertSame('Under review', $subject->fresh()->classification);
    }

    public function test_manual_risk_override_is_preserved(): void
    {
        $subject = Subject::factory()->create(['risk_score' => 86, 'risk_override' => true]);
        Offense::factory()->create(['subject_id' => $subject->id, 'category' => 'other-theft']);
        app(RiskScoreService::class)->recalculate($subject);
        $this->assertSame(86, $subject->fresh()->risk_score);
    }
}
