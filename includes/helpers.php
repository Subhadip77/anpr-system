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

function money(mixed $value): float
{
    return max(0, round((float) $value, 2));
}

function nextBillNumber(PDO $pdo): string
{
    return 'NLB-' . date('Y') . '-' . str_pad((string) ((int) $pdo->query('SELECT COUNT(*) FROM bills')->fetchColumn() + 1), 5, '0', STR_PAD_LEFT);
}

function nursingHomeSettings(PDO $pdo): array
{
    $settings = $pdo->query('SELECT * FROM settings ORDER BY id ASC LIMIT 1')->fetch();
    return $settings ?: [
        'nursing_home_name' => 'New Life Nursing Home',
        'establishment_type' => 'Clinical Establishment',
        'registration_no' => '50339074',
        'address' => 'Uttar Darua, Darua, Contai, Purba Medinipur',
        'phone' => '8918782920 / 6294264974',
        'email' => 'newlifenursinghome777@gmail.com',
    ];
}