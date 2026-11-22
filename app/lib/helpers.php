<?php
/** Small shared helpers: escaping, URLs, redirects, request accessors, formatting. */

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return rtrim($GLOBALS['CONFIG']['base_url'], '/') . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function post(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
}

function query_param(string $key, string $default = ''): string
{
    return isset($_GET[$key]) ? trim((string) $_GET[$key]) : $default;
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '';
}

function user_agent(): string
{
    return mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
}

function bytes_for_humans(int $bytes): string
{
    if ($bytes >= 1048576) { return round($bytes / 1048576, 1) . ' MB'; }
    if ($bytes >= 1024)    { return round($bytes / 1024, 1) . ' KB'; }
    return $bytes . ' B';
}

function status_badge(string $status): string
{
    $map = [
        'pending'  => ['pill--pending', 'bi-hourglass-split'],
        'approved' => ['pill--approved', 'bi-check2-circle'],
        'rejected' => ['pill--rejected', 'bi-x-circle'],
    ];
    [$class, $icon] = $map[$status] ?? ['pill--neutral', 'bi-circle'];
    return '<span class="pill ' . $class . '"><i class="bi ' . $icon . '"></i>' . e(ucfirst($status)) . '</span>';
}

/** Colored label for a document category (6 rotating hues, theme-aware). */
function category_chip(?int $categoryId, string $name): string
{
    $tint = 'cat-tint-' . ((int) $categoryId % 6);
    return '<span class="chip ' . $tint . '"><i class="bi bi-folder2"></i>' . e($name) . '</span>';
}

/** Colored label for a user role. */
function role_chip(string $role): string
{
    $map = [
        'admin'  => ['role-admin', 'bi-shield-lock'],
        'staff'  => ['role-staff', 'bi-person-badge'],
        'viewer' => ['role-viewer', 'bi-eye'],
    ];
    [$class, $icon] = $map[$role] ?? ['role-viewer', 'bi-person'];
    return '<span class="role-chip ' . $class . '"><i class="bi ' . $icon . '"></i>' . e(ucfirst($role)) . '</span>';
}

/** Two-letter initials for avatar circles. */
function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $ini   = '';
    foreach ($parts as $part) {
        if ($part !== '') { $ini .= mb_strtoupper(mb_substr($part, 0, 1)); }
        if (mb_strlen($ini) >= 2) { break; }
    }
    return $ini !== '' ? $ini : '?';
}

function avatar(string $name, string $extraClass = ''): string
{
    return '<span class="avatar ' . e($extraClass) . '">' . e(initials($name)) . '</span>';
}

/** Bootstrap-Icons class for a file extension. */
function file_icon(string $ext): string
{
    $map = [
        'pdf' => 'bi-file-earmark-pdf', 'doc' => 'bi-file-earmark-word', 'docx' => 'bi-file-earmark-word',
        'xls' => 'bi-file-earmark-excel', 'xlsx' => 'bi-file-earmark-excel',
        'ppt' => 'bi-file-earmark-ppt', 'pptx' => 'bi-file-earmark-ppt',
        'jpg' => 'bi-file-earmark-image', 'jpeg' => 'bi-file-earmark-image', 'png' => 'bi-file-earmark-image',
        'txt' => 'bi-file-earmark-text',
    ];
    return $map[strtolower($ext)] ?? 'bi-file-earmark';
}
