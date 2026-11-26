<?php
/**
 * Authentication (SR-1, SR-2): login/logout, session identity, guards.
 */
function current_user(): ?array
{
    static $cached = false;
    if ($cached === false) {
        if (empty($_SESSION['user_id'])) {
            $cached = null;
        } else {
            $stmt = db()->prepare(
                "SELECT u.*, r.role_name FROM users u JOIN roles r ON r.role_id = u.role_id
                 WHERE u.user_id = ? AND u.status = 'active'"
            );
            $stmt->execute([(int) $_SESSION['user_id']]);
            $row    = $stmt->fetch();
            $cached = $row ?: null;
            if ($cached === null) {
                unset($_SESSION['user_id']); // account was deactivated mid-session
            }
        }
    }
    return $cached;
}

function require_login(): void
{
    if (current_user() === null) {
        redirect('login.php');
    }
}

function fetch_user(int $userId): ?array
{
    $stmt = db()->prepare(
        'SELECT u.*, r.role_name FROM users u JOIN roles r ON r.role_id = u.role_id WHERE u.user_id = ?'
    );
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

function active_admin_count(): int
{
    return (int) db()->query(
        "SELECT COUNT(*) AS c FROM users u JOIN roles r ON r.role_id = u.role_id
         WHERE r.role_name = 'admin' AND u.status = 'active'"
    )->fetch()['c'];
}

/**
 * Attempt a login. @return array{0:bool,1:string} [success, message]
 */
function attempt_login(string $username, string $password): array
{
    $username = trim($username);
    if ($username === '' || $password === '') {
        return [false, 'Please enter both username and password.'];
    }

    // Simple throttling: 5 failed attempts per username in 15 minutes (SR-2 enhancement)
    $check = db()->prepare(
        'SELECT COUNT(*) AS c FROM login_attempts
         WHERE username_tried = ? AND success = 0 AND created_at > (NOW() - INTERVAL 15 MINUTE)'
    );
    $check->execute([$username]);
    if ((int) $check->fetch()['c'] >= 5) {
        log_activity(null, 'LOGIN_THROTTLED', null, null, 'username=' . $username);
        return [false, 'Too many failed attempts. Please try again in 15 minutes.'];
    }

    $stmt = db()->prepare('SELECT user_id, password_hash, status, full_name FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    $ok = $user ? password_verify($password, $user['password_hash']) : false;

    if (!$ok) {
        db()->prepare('INSERT INTO login_attempts (username_tried, ip_address, success) VALUES (?, ?, 0)')
            ->execute([$username, client_ip()]);
        if ($user) {
            db()->prepare('UPDATE users SET failed_attempts = failed_attempts + 1 WHERE user_id = ?')
                ->execute([(int) $user['user_id']]);
            log_activity((int) $user['user_id'], 'LOGIN_FAILED');
        } else {
            log_activity(null, 'LOGIN_FAILED', null, null, 'unknown username=' . $username);
        }
        return [false, 'Invalid username or password.'];
    }

    if ($user['status'] !== 'active') {
        log_activity((int) $user['user_id'], 'LOGIN_DENIED_INACTIVE');
        return [false, 'This account has been deactivated. Contact the administrator.'];
    }

    db()->prepare('UPDATE users SET failed_attempts = 0, last_login = NOW() WHERE user_id = ?')
        ->execute([(int) $user['user_id']]);
    db()->prepare('INSERT INTO login_attempts (username_tried, ip_address, success) VALUES (?, ?, 1)')
        ->execute([$username, client_ip()]);
    log_activity((int) $user['user_id'], 'LOGIN_SUCCESS');

    session_regenerate_id(true); // defeats session fixation (SR-7)
    $_SESSION['user_id']       = (int) $user['user_id'];
    $_SESSION['last_activity'] = time();
    return [true, 'Welcome back, ' . ($user['full_name'] ?: $username) . '!'];
}

function do_logout(): void
{
    $u = current_user();
    if ($u) {
        log_activity((int) $u['user_id'], 'LOGOUT');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
    }
    session_destroy();
    session_start();
}
