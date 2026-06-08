<?php

namespace App\Support;

class FaceRecognition
{
    public const DESCRIPTOR_SIZE = 256;
    public const MATCH_THRESHOLD = 0.55;

    /**
     * @return array<string, list<string>>
     */
    public static function rules(string $key = 'face_descriptor'): array
    {
        return [
            $key => ['required', 'array', 'size:' . self::DESCRIPTOR_SIZE],
            $key . '.*' => ['required', 'numeric', 'between:-5,5'],
        ];
    }

    /**
     * @param  array<int, mixed>  $descriptor
     * @return list<float>
     */
    public static function normalize(array $descriptor): array
    {
        return array_map(
            fn (mixed $value): float => round((float) $value, 6),
            array_values($descriptor)
        );
    }

    /**
     * @param  array<int, mixed>  $known
     * @param  array<int, mixed>  $candidate
     */
    public static function distance(array $known, array $candidate): float
    {
        $known = self::normalize($known);
        $candidate = self::normalize($candidate);

        if (count($known) !== self::DESCRIPTOR_SIZE || count($candidate) !== self::DESCRIPTOR_SIZE) {
            return INF;
        }

        $sum = 0.0;

        foreach ($known as $index => $knownValue) {
            $delta = $knownValue - $candidate[$index];
            $sum += $delta * $delta;
        }

        return sqrt($sum / self::DESCRIPTOR_SIZE);
    }

    /**
     * @param  array<int, mixed>|null  $known
     * @param  array<int, mixed>  $candidate
     */
    public static function matches(?array $known, array $candidate): bool
    {
        if (! $known) {
            return false;
        }

        return self::distance($known, $candidate) <= self::MATCH_THRESHOLD;
    }
}
