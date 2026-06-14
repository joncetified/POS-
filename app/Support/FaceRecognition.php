<?php

namespace App\Support;

use InvalidArgumentException;

class FaceRecognition
{
    private const DESCRIPTOR_SIZE = 128;
    private const MATCH_THRESHOLD = 0.6;

    /**
     * @return list<float>
     */
    public static function descriptorFromJson(string $descriptor): array
    {
        $values = json_decode($descriptor, true);

        if (! is_array($values) || count($values) !== self::DESCRIPTOR_SIZE) {
            throw new InvalidArgumentException('Data wajah tidak valid. Daftarkan ulang wajah dari profil.');
        }

        return array_map(function (mixed $value): float {
            if (! is_numeric($value)) {
                throw new InvalidArgumentException('Data wajah tidak valid. Daftarkan ulang wajah dari profil.');
            }

            return (float) $value;
        }, array_values($values));
    }

    /**
     * @param list<float> $descriptor
     */
    public static function encode(array $descriptor): string
    {
        return json_encode($descriptor, JSON_THROW_ON_ERROR);
    }

    public static function isMatch(string $storedDescriptor, string $attemptDescriptor): bool
    {
        $stored = self::descriptorFromJson($storedDescriptor);
        $attempt = self::descriptorFromJson($attemptDescriptor);

        return self::distance($stored, $attempt) <= self::MATCH_THRESHOLD;
    }

    /**
     * @param list<float> $stored
     * @param list<float> $attempt
     */
    private static function distance(array $stored, array $attempt): float
    {
        $sum = 0.0;

        foreach ($stored as $index => $value) {
            $difference = $value - $attempt[$index];
            $sum += $difference * $difference;
        }

        return sqrt($sum);
    }
}
