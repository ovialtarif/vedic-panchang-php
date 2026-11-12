<?php

namespace App\Services\VedicPanchang;

use DateTimeImmutable;
use SwissEph\FFI\SwissEphFFI;

/**
 * Calculates factual Navagraha positions; it does not attach astrological predictions.
 */
final class GrahaCalculator
{
    private const BODIES = [
        ['id' => 'surya', 'name' => 'Surya', 'english' => 'Sun', 'body' => SwissEphFFI::SE_SUN],
        ['id' => 'chandra', 'name' => 'Chandra', 'english' => 'Moon', 'body' => SwissEphFFI::SE_MOON],
        ['id' => 'mangal', 'name' => 'Mangal', 'english' => 'Mars', 'body' => SwissEphFFI::SE_MARS],
        ['id' => 'budha', 'name' => 'Budha', 'english' => 'Mercury', 'body' => SwissEphFFI::SE_MERCURY],
        ['id' => 'guru', 'name' => 'Guru', 'english' => 'Jupiter', 'body' => SwissEphFFI::SE_JUPITER],
        ['id' => 'shukra', 'name' => 'Shukra', 'english' => 'Venus', 'body' => SwissEphFFI::SE_VENUS],
        ['id' => 'shani', 'name' => 'Shani', 'english' => 'Saturn', 'body' => SwissEphFFI::SE_SATURN],
    ];

    public function __construct(private readonly SwissEphemerisEngine $engine)
    {
    }

    /** @return array{at: string, lunar_node: string, grahas: list<array<string, int|float|string|bool>>} */
    public function calculate(DateTimeImmutable $instant, string $lunarNode = 'mean'): array
    {
        if (! in_array($lunarNode, ['mean', 'true'], true)) {
            throw new \InvalidArgumentException('Lunar node must be mean or true.');
        }

        $positions = [];
        foreach (self::BODIES as $body) {
            $positions[] = $this->formatPosition(
                $body['id'],
                $body['name'],
                $body['english'],
                $this->engine->siderealLongitudeAndSpeed($instant, $body['body']),
            );
        }

        $nodeId = $lunarNode === 'mean' ? SwissEphFFI::SE_MEAN_NODE : SwissEphFFI::SE_TRUE_NODE;
        $rahu = $this->engine->siderealLongitudeAndSpeed($instant, $nodeId);
        $positions[] = $this->formatPosition('rahu', 'Rahu', 'North node', $rahu);
        $positions[] = $this->formatPosition('ketu', 'Ketu', 'South node', [
            'longitude' => PanchangMath::normalizeDegrees($rahu['longitude'] + 180.0),
            'speed' => $rahu['speed'],
        ]);

        return [
            'at' => $instant->format(DATE_ATOM),
            'lunar_node' => $lunarNode,
            'grahas' => $positions,
        ];
    }

    /** @param array{longitude: float, speed: float} $position
     *  @return array{id: string, name: string, english: string, longitude: float, rashi_number: int, rashi: string, degree_in_rashi: float, nakshatra_number: int, nakshatra: string, pada: int, retrograde: bool}
     */
    private function formatPosition(string $id, string $name, string $english, array $position): array
    {
        $longitude = PanchangMath::normalizeDegrees($position['longitude']);
        $rashi = PanchangMath::rashi($longitude);
        $nakshatra = PanchangMath::nakshatra($longitude);

        return [
            'id' => $id,
            'name' => $name,
            'english' => $english,
            'longitude' => $longitude,
            'rashi_number' => $rashi['number'],
            'rashi' => $rashi['name'],
            'degree_in_rashi' => fmod($longitude, 30.0),
            'nakshatra_number' => $nakshatra['number'],
            'nakshatra' => $nakshatra['name'],
            'pada' => $nakshatra['pada'],
            'retrograde' => $position['speed'] < 0.0,
        ];
    }
}
