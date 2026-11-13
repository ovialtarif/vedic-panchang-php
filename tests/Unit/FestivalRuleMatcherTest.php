<?php

namespace Tests\Unit;

use App\Services\VedicPanchang\Festivals\FestivalRuleMatcher;
use Tests\TestCase;

final class FestivalRuleMatcherTest extends TestCase
{
    public function test_month_specific_rule_matches_regular_amanta_month_and_tithi(): void
    {
        $matcher = new FestivalRuleMatcher();
        $rule = ['id' => 'diwali', 'tithi' => 30, 'amanta_month' => 'ashwin'];
        $instance = [
            'number' => 30,
            'amanta' => ['id' => 'ashwin', 'name' => 'Ashwin', 'is_adhika' => false],
        ];

        self::assertTrue($matcher->matchesLunar($rule, $instance));
        self::assertFalse($matcher->matchesLunar($rule, [...$instance, 'number' => 29]));
    }

    public function test_month_specific_rules_skip_adhika_months_by_default(): void
    {
        $matcher = new FestivalRuleMatcher();
        $rule = ['tithi' => 11, 'amanta_month' => 'magha'];
        $instance = [
            'number' => 11,
            'amanta' => ['id' => 'adhika-magha', 'name' => 'Magha', 'is_adhika' => true],
        ];

        self::assertFalse($matcher->matchesLunar($rule, $instance));
    }

    public function test_any_month_rule_defaults_to_both_regular_and_adhika_months(): void
    {
        $matcher = new FestivalRuleMatcher();
        $rule = ['tithi' => 15, 'amanta_month' => 'any'];
        $regular = ['number' => 15, 'amanta' => ['id' => 'ashwin', 'is_adhika' => false]];
        $adhika = ['number' => 15, 'amanta' => ['id' => 'adhika-ashwin', 'is_adhika' => true]];

        self::assertTrue($matcher->matchesLunar($rule, $regular));
        self::assertTrue($matcher->matchesLunar($rule, $adhika));
    }

    public function test_skip_months_excludes_regular_month_but_not_adhika_month(): void
    {
        $matcher = new FestivalRuleMatcher();
        $rule = ['tithi' => 4, 'amanta_month' => 'any', 'skip_months' => ['bhadrapada']];
        $regular = ['number' => 4, 'amanta' => ['id' => 'bhadrapada', 'is_adhika' => false]];
        $adhika = ['number' => 4, 'amanta' => ['id' => 'adhika-bhadrapada', 'is_adhika' => true]];

        self::assertFalse($matcher->matchesLunar($rule, $regular));
        self::assertTrue($matcher->matchesLunar($rule, $adhika));
    }

    public function test_month_name_templates_use_requested_month_system(): void
    {
        $matcher = new FestivalRuleMatcher();
        $rule = ['name' => '{masa} Purnima'];
        $instance = [
            'amanta' => ['name' => 'Ashwin', 'is_adhika' => false],
            'purnimanta' => ['name' => 'Kartika', 'is_adhika' => false],
        ];

        self::assertSame('Ashwin Purnima', $matcher->renderLunarName($rule, $instance, 'amanta'));
        self::assertSame('Kartika Purnima', $matcher->renderLunarName($rule, $instance, 'purnimanta'));
    }

    public function test_solar_rules_respect_specific_and_generic_sign_claims(): void
    {
        $matcher = new FestivalRuleMatcher();
        $event = ['sign' => 9, 'jd' => 2460000.0];

        self::assertTrue($matcher->matchesSolar(['sign' => 'makara'], $event));
        self::assertFalse($matcher->matchesSolar(['sign' => 'kumbha'], $event));
        self::assertFalse($matcher->matchesSolar(['sign' => 'any'], $event, [9]));
        self::assertSame('Makara Sankranti', $matcher->renderSolarName(['name' => '{sign} Sankranti'], 9));
    }
}
