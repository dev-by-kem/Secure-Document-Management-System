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

/**
 * True when the app runs on a developer's own machine (XAMPP localhost).
 * Uses SERVER_ADDR — the server's own IP, which the browser cannot fake —
 * so demo-account hints can stay on localhost but never appear on the
 * public staging server (docs/09 §4 security rules).
 */
function is_localhost(): bool
{
    return in_array($_SERVER['SERVER_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
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
        'pending'  => ['Pending review', 'pill--pending', 'bi-hourglass-split'],
        'approved' => ['Approved', 'pill--approved', 'bi-check2-circle'],
        'rejected' => ['Rejected', 'pill--rejected', 'bi-x-circle'],
        'draft'    => ['Draft', 'pill--neutral', 'bi-file-earmark'],
        'deleted'  => ['Deleted', 'pill--deleted', 'bi-trash3'],
    ];
    [$label, $class, $icon] = $map[$status] ?? [ucfirst($status), 'pill--neutral', 'bi-circle'];
    return '<span class="pill ' . $class . '"><i class="bi ' . $icon . '" aria-hidden="true"></i>' . e($label) . '</span>';
}

/** Human-readable, semantic badge for audit and activity action codes. */
function action_badge(string $action): string
{
    $map = [
        'LOGIN_SUCCESS'         => ['Login successful', 'pill--approved', 'bi-box-arrow-in-right'],
        'LOGIN_FAILED'          => ['Login failed', 'pill--rejected', 'bi-shield-x'],
        'LOGIN_DENIED_INACTIVE' => ['Login blocked', 'pill--rejected', 'bi-person-slash'],
        'LOGIN_THROTTLED'       => ['Login temporarily limited', 'pill--pending', 'bi-hourglass-split'],
        'LOGOUT'                => ['Signed out', 'pill--neutral', 'bi-box-arrow-right'],
        'UPLOAD_DOCUMENT'       => ['Document uploaded', 'pill--info', 'bi-cloud-arrow-up'],
        'DOWNLOAD_DOCUMENT'     => ['Document downloaded', 'pill--info', 'bi-download'],
        'VIEW_DOCUMENT'         => ['Document viewed', 'pill--info', 'bi-eye'],
        'EDIT_DOCUMENT'         => ['Document updated', 'pill--info', 'bi-pencil-square'],
        'UPDATE_DOCUMENT'       => ['Document updated', 'pill--info', 'bi-pencil-square'],
        'APPROVE_DOCUMENT'      => ['Document approved', 'pill--approved', 'bi-check-circle'],
        'REJECT_DOCUMENT'       => ['Document rejected', 'pill--rejected', 'bi-x-circle'],
        'DELETE_DOCUMENT'       => ['Document deleted', 'pill--rejected', 'bi-trash3'],
        'CREATE_USER'           => ['User created', 'pill--approved', 'bi-person-plus'],
        'UPDATE_USER'           => ['User updated', 'pill--info', 'bi-person-gear'],
        'ACTIVATE_USER'         => ['User activated', 'pill--approved', 'bi-person-check'],
        'DEACTIVATE_USER'       => ['User deactivated', 'pill--pending', 'bi-person-dash'],
        'RESET_PASSWORD'        => ['Password reset', 'pill--pending', 'bi-key'],
        'CHANGE_PASSWORD'       => ['Password changed', 'pill--info', 'bi-key'],
        'CREATE_CATEGORY'       => ['Category created', 'pill--approved', 'bi-folder-plus'],
        'UPDATE_CATEGORY'       => ['Category updated', 'pill--info', 'bi-tags'],
        'DELETE_CATEGORY'       => ['Category deleted', 'pill--rejected', 'bi-trash3'],
        'REPORT_GENERATED'      => ['Report generated', 'pill--info', 'bi-bar-chart'],
        'ACCESS_DENIED'         => ['Access denied', 'pill--rejected', 'bi-slash-circle'],
        'CHECKSUM_MISMATCH'     => ['File integrity check failed', 'pill--rejected', 'bi-shield-exclamation'],
    ];
    [$label, $class, $icon] = $map[$action] ?? ['System activity', 'pill--neutral', 'bi-activity'];
    return '<span class="pill ' . $class . '" data-action-code="' . e($action) . '" title="' . e($action) . '">'
         . '<i class="bi ' . $icon . '" aria-hidden="true"></i>' . e($label) . '</span>';
}

/** Colored label for a document category (6 rotating hues, theme-aware). */
function category_chip(?int $categoryId, string $name): string
{
    $tint = 'cat-tint-' . ((int) $categoryId % 6);
    return '<span class="chip ' . $tint . '"><i class="bi bi-folder2" aria-hidden="true"></i>' . e($name) . '</span>';
}

/** Human-readable title-cased label for a system role. */
function role_label(string $role): string
{
    $map = [
        'admin'  => 'Administrator',
        'staff'  => 'Staff',
        'viewer' => 'Viewer',
    ];
    return $map[strtolower($role)] ?? ucfirst($role);
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
    return '<span class="role-chip ' . $class . '"><i class="bi ' . $icon . '" aria-hidden="true"></i>' . e(role_label($role)) . '</span>';
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
    return '<span class="avatar ' . e($extraClass) . '" aria-hidden="true">' . e(initials($name)) . '</span>';
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

/** Standardize local timestamp display across the UI. */
function format_datetime(?string $dt): string
{
    if ($dt === null || trim($dt) === '') {
        return '—';
    }
    $ts = strtotime($dt);
    if ($ts === false) {
        return $dt;
    }
    return date('M j, Y · g:i A', $ts);
}

/** Render accessible pagination + result summary while preserving active filters. */
function render_pagination(int $page, int $totalPages, int $totalRows, int $perPage, string $path, array $queryParams = []): string
{
    if ($totalRows <= 0) {
        return '';
    }
    $start = ($page - 1) * $perPage + 1;
    $end   = min($totalRows, $page * $perPage);
    $summary = '<div class="small text-muted">Showing <strong>' . $start . '–' . $end . '</strong> of <strong>' . $totalRows . '</strong></div>';

    if ($totalPages <= 1) {
        return '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3 px-1">' . $summary . '</div>';
    }

    $buildUrl = static function (int $p) use ($path, $queryParams): string {
        $params = array_filter($queryParams, static fn ($v) => $v !== '' && $v !== null);
        $params['page'] = $p;
        return url($path) . '?' . http_build_query($params);
    };

    $items = '';
    $prevDisabled = $page <= 1 ? ' disabled' : '';
    $items .= '<li class="page-item' . $prevDisabled . '"><a class="page-link" href="' . ($page > 1 ? e($buildUrl($page - 1)) : '#') . '" aria-label="Previous page">Prev</a></li>';

    $windowStart = max(1, $page - 2);
    $windowEnd   = min($totalPages, $page + 2);
    if ($windowStart > 1) {
        $items .= '<li class="page-item"><a class="page-link" href="' . e($buildUrl(1)) . '">1</a></li>';
        if ($windowStart > 2) {
            $items .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
    }
    for ($i = $windowStart; $i <= $windowEnd; $i++) {
        $active = $i === $page ? ' active" aria-current="page' : '';
        $items .= '<li class="page-item' . ($i === $page ? ' active' : '') . '"><a class="page-link" href="' . e($buildUrl($i)) . '"' . ($i === $page ? ' aria-current="page"' : '') . '>' . $i . '</a></li>';
    }
    if ($windowEnd < $totalPages) {
        if ($windowEnd < $totalPages - 1) {
            $items .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
        $items .= '<li class="page-item"><a class="page-link" href="' . e($buildUrl($totalPages)) . '">' . $totalPages . '</a></li>';
    }

    $nextDisabled = $page >= $totalPages ? ' disabled' : '';
    $items .= '<li class="page-item' . $nextDisabled . '"><a class="page-link" href="' . ($page < $totalPages ? e($buildUrl($page + 1)) : '#') . '" aria-label="Next page">Next</a></li>';

    return '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3 px-1">'
         . $summary
         . '<nav aria-label="Pagination"><ul class="pagination pagination-sm mb-0">' . $items . '</ul></nav>'
         . '</div>';
}
