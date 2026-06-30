<?php

namespace App\Services;

use App\Models\Contact;

class PhoneNumberSanitizer
{
    public const INVALID_REASON = 'Nomor harus nomor Indonesia aktif dengan format 08xxx, 628xxx, atau +628xxx.';

    public function normalize(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }

        if (! preg_match('/^628[0-9]{7,12}$/', $digits)) {
            return null;
        }

        return $digits;
    }

    /**
     * @param array<int, string|null> $numbers
     * @return array{numbers: array<int, string>, invalid: array<int, string>, duplicates: int}
     */
    public function normalizeMany(array $numbers): array
    {
        $accepted = [];
        $invalid = [];
        $seen = [];
        $duplicates = 0;

        foreach ($numbers as $number) {
            $raw = trim((string) $number);

            if ($raw === '') {
                continue;
            }

            $normalized = $this->normalize($raw);

            if ($normalized === null) {
                $invalid[] = $raw;
                continue;
            }

            if (isset($seen[$normalized])) {
                $duplicates++;
                continue;
            }

            $seen[$normalized] = true;
            $accepted[] = $normalized;
        }

        return [
            'numbers' => $accepted,
            'invalid' => $invalid,
            'duplicates' => $duplicates,
        ];
    }

    /**
     * @param array<int, string> $numbers
     * @return array{numbers: array<int, string>, blocked: array<int, string>}
     */
    public function excludeBlockedContacts(array $numbers): array
    {
        if ($numbers === []) {
            return ['numbers' => [], 'blocked' => []];
        }

        $blocked = Contact::query()
            ->whereIn('number', $numbers)
            ->whereIn('status', ['blocked', 'unsubscribed'])
            ->pluck('number')
            ->unique()
            ->values()
            ->all();

        if ($blocked === []) {
            return ['numbers' => array_values($numbers), 'blocked' => []];
        }

        $blockedLookup = array_fill_keys($blocked, true);

        return [
            'numbers' => array_values(array_filter($numbers, fn ($number) => ! isset($blockedLookup[$number]))),
            'blocked' => $blocked,
        ];
    }
}
