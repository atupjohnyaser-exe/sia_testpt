<?php

namespace Tests\Unit;

use App\Enums\ThreatLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ThreatLevelTest extends TestCase
{
    public static function bounties(): array
    {
        return [
            'zero' => [0, 1],
            'just under level 2' => [9_999, 1],
            'level 2' => [10_000, 2],
            'just under level 3' => [99_999, 2],
            'level 3' => [100_000, 3],
            'level 4' => [500_000, 4],
            'level 5' => [2_000_000, 5],
            'level 6' => [10_000_000, 6],
            'far above' => [999_999_999, 6],
        ];
    }

    #[DataProvider('bounties')]
    public function test_level_follows_the_bounty(int $bounty, int $expected): void
    {
        $this->assertSame($expected, ThreatLevel::fromBounty($bounty)->value);
    }

    public function test_every_level_has_a_label_and_a_response(): void
    {
        foreach (ThreatLevel::cases() as $level) {
            $this->assertNotSame('', $level->label());
            $this->assertNotSame('', $level->response());
        }
    }
}
