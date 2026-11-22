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
