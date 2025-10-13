<?php
declare(strict_types=1);
require __DIR__.'/auth.php';
auth_logout();
auth_cookie_clear(AUTH_REMEMBER_COOKIE);
header('Location: login.php');
exit;
