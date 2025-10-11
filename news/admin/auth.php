<?php declare(strict_types=1);
// Simple admin auth + CSRF helpers
// Include this at the top of every admin page (dashboard.php, edit.php, categories.php, delete.php ...)
// If the current script is not login.php, the user must be logged in.

// Start session once
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Redirect to login.php when not authenticated (except on login.php itself)
$__script = basename($_SERVER['SCRIPT_NAME'] ?? '');
if ($__script !== 'login.php') {
    if (empty($_SESSION['admin_logged_in'])) {
        header('Location: login.php');
        exit;
    }
}

// ---- CSRF protection ----
// Use csrf_field() inside every <form method="post"> in the admin UI
// Call csrf_validate() at the top of PHP that handles POST mutations.

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    $t = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="_csrf" value="'.$t.'">';
}

function csrf_validate(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $posted = $_POST['_csrf'] ?? '';
        $valid  = isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$posted);
        if (!$valid) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo "CSRF validation failed";
            exit;
        }
    }
}

// Helper to quickly check auth elsewhere if needed
function is_admin(): bool {
    return !empty($_SESSION['admin_logged_in']);
}