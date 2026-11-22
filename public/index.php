<?php
require_once __DIR__ . '/../app/bootstrap.php';
redirect(current_user() !== null ? 'dashboard.php' : 'login.php');
