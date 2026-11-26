<?php
/** One-shot success/error messages, shown after redirects (PRG pattern). */
function flash_set(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

/**
 * @return array<int, array{type:string, msg:string}>
 */
function flash_take(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/** Store one-shot old input and field-level errors across a PRG redirect. */
function form_state_set(array $old, array $errors): void
{
    $_SESSION['form_state'] = ['old' => $old, 'errors' => $errors];
}

/**
 * @return array{old: array<string, string>, errors: array<string, string>}
 */
function form_state_take(): array
{
    $state = $_SESSION['form_state'] ?? ['old' => [], 'errors' => []];
    unset($_SESSION['form_state']);
    return [
        'old'    => is_array($state['old'] ?? null) ? $state['old'] : [],
        'errors' => is_array($state['errors'] ?? null) ? $state['errors'] : [],
    ];
}
