<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function nullableString(array $source, string $key): ?string
{
    $value = trim((string) ($source[$key] ?? ''));
    return $value === '' ? null : $value;
}

function nullableDate(array $source, string $key): ?string
{
    $value = nullableString($source, $key);
    return $value && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
}

function nullableTime(array $source, string $key): ?string
{
    $value = nullableString($source, $key);
    return $value && preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value) ? $value : null;
}

function displayDate(?string $value): string
{
    if (!$value) return '';
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date ? $date->format('d/m/y') : $value;
}

function formattedAddress(array $record): string
{
    $parts = [$record['address'] ?? null, $record['village'] ?? null, $record['post_office'] ?? null, $record['police_station'] ?? null, $record['district'] ?? null, $record['block'] ?? null, $record['gp_municipality'] ?? null];
    $uniqueParts = [];
    foreach ($parts as $part) {
        $part = trim((string) $part);
        $key = strtolower($part);
        if ($part !== '' && !isset($uniqueParts[$key])) $uniqueParts[$key] = $part;
    }
    $address = implode(', ', $uniqueParts);
    return $address . (!empty($record['pin']) ? ($address !== '' ? ' - ' : '') . $record['pin'] : '');
}

function nursingHomeSettings(PDO $pdo): array
{
    $settings = $pdo->query('SELECT * FROM settings ORDER BY id ASC LIMIT 1')->fetch();
    return $settings ?: ['nursing_home_name' => 'New Life Nursing Home', 'establishment_type' => 'Clinical Establishment', 'registration_no' => '', 'address' => '', 'phone' => '', 'email' => ''];
}
