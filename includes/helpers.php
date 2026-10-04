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

function displayDate(?string $value): string
{
    if (!$value) return '';
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date ? $date->format('d/m/y') : $value;
}

function formattedAddress(array $bill): string
{
    $parts = [
        $bill['address'] ?? null,
        $bill['village'] ?? null,
        $bill['post_office'] ?? null,
        $bill['police_station'] ?? null,
        $bill['district'] ?? null,
        $bill['block'] ?? null,
        $bill['gp_municipality'] ?? null,
    ];
    $uniqueParts = [];
    foreach ($parts as $part) {
        $part = trim((string) $part);
        $key = strtolower($part);
        if ($part !== '' && !isset($uniqueParts[$key])) $uniqueParts[$key] = $part;
    }
    $address = implode(', ', $uniqueParts);
    return $address . (!empty($bill['pin']) ? ($address !== '' ? ' - ' : '') . $bill['pin'] : '');
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
    $year = date('Y');
    $statement = $pdo->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(bill_number, '-', -1) AS UNSIGNED)) FROM bills WHERE bill_number LIKE ?");
    $statement->execute(['NLB-' . $year . '-%']);
    $nextNumber = ((int) $statement->fetchColumn()) + 1;
    return 'NLB-' . $year . '-' . str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
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