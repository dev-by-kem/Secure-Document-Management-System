<?php
require_once __DIR__ . '/../app/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    do_logout();
    redirect('login.php');
}
redirect(current_user() !== null ? 'dashboard.php' : 'login.php');
