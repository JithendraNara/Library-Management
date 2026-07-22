<?php
declare(strict_types=1);

/**
 * Shared helpers used by index.php (router) and controllers/.
 * Kept dependency-free so each controller can require it cheaply.
 */

/** HTML-escape a value for safe output. */
function e(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

/** Redirect and terminate. */
function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

/** Render a view inside the global layout. */
function render(string $view, array $data = [], string $title = 'Central Library'): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require __DIR__ . '/../views/' . $view . '.php';
    $content = ob_get_clean();
    require __DIR__ . '/../views/layout.php';
}

/** Start the session exactly once per request (avoids duplicate-start notices). */
function startSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

/** Pull a flash message set via session, if any. */
function flash(): ?array
{
    startSession();
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function setFlash(string $type, string $message): void
{
    startSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Get (or lazily create) the per-session CSRF token. */
function csrfToken(): string
{
    startSession();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Render a hidden CSRF input for inclusion in every state-changing form. */
function csrfField(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}

/** True when the submitted token matches the session token. */
function csrfIsValid(?string $sent): bool
{
    return is_string($sent) && $sent !== '' && hash_equals(csrfToken(), $sent);
}

/**
 * Validate the CSRF token on a POST request.
 * Aborts with 419 if the token is missing or does not match.
 */
function requireCsrf(): void
{
    if (csrfIsValid($_POST['csrf'] ?? null)) {
        return;
    }
    http_response_code(419);
    render('error', ['error' => 'Invalid or missing CSRF token. Please go back and retry.'], 'Security check failed');
    exit;
}
