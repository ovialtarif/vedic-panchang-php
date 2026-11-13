<?php

namespace Tests\Unit;

use App\Services\VedicPanchang\Festivals\FestivalRuleRepository;
use PHPUnit\Framework\TestCase;

final class FestivalRuleRepositoryTest extends TestCase
{
    public function test_north_indian_smarta_profile_loads_validated_lunar_and_solar_rules(): void
    {
        $rules = (new FestivalRuleRepository())->load();

        self::assertSame('north-indian-smarta', $rules['id']);
        self::assertSame('2026.10.5', $rules['rule_version']);
        self::assertNotEmpty($rules['lunar']);
        self::assertNotEmpty($rules['solar']);

        $diwali = array_values(array_filter(
            $rules['lunar'],
            static fn (array $rule): bool => $rule['id'] === 'diwali',
        ));

        self::assertCount(1, $diwali);
        self::assertSame(30, $diwali[0]['tithi']);
        self::assertSame('pradosh', $diwali[0]['kala']);
        self::assertSame(['lakshmi_puja', 'pradosh', 'nishita'], $diwali[0]['puja']);
    }

    public function test_loaded_rule_set_is_cached_for_the_repository_instance(): void
    {
        $repository = new FestivalRuleRepository();

        self::assertSame($repository->load(), $repository->load());
    }

    public function test_invalid_profile_identifier_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new FestivalRuleRepository())->load('../outside');
    }
}
