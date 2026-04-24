<?php

function cleanInput($input)
{
    return filter_var($input, FILTER_SANITIZE_SPECIAL_CHARS);
}

function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function isValidPhone($phone)
{
    return (ctype_digit($phone) && strlen($phone) == 10);
}

function dnd($variable)
{
    echo '<pre>';
    var_dump($variable);
    echo '</pre>';
    die;
}

function guestContactDefaults()
{
    return [
        'name' => 'Guest',
        'email' => 'guest@gmail.com',
        'phone' => '0000000000',
    ];
}

function buildGuestUser($data = [])
{
    $defaults = guestContactDefaults();
    $guest = new stdClass();
    $guest->id = 0;
    $guest->name = trim((string)($data['name'] ?? '')) ?: $defaults['name'];
    $guest->email = trim((string)($data['email'] ?? '')) ?: $defaults['email'];
    $guest->phone = trim((string)($data['phone'] ?? '')) ?: $defaults['phone'];
    $guest->role = 'guest';

    return $guest;
}

function isGuestUser($user)
{
    return is_object($user) && (($user->role ?? '') === 'guest');
}

function rememberGuestContact($name, $email, $phone = '')
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    if (!isGuestUser($_SESSION['user'] ?? null)) {
        return;
    }

    $_SESSION['user'] = buildGuestUser([
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
    ]);
}

function getGuestTicketIds()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return [];
    }

    $ids = $_SESSION['guest_ticket_ids'] ?? [];
    if (!is_array($ids)) {
        return [];
    }

    $cleanIds = [];
    foreach ($ids as $id) {
        $ticketId = (int)$id;
        if ($ticketId > 0) {
            $cleanIds[$ticketId] = $ticketId;
        }
    }

    return array_values($cleanIds);
}

function rememberGuestTicket($ticketId)
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    if (!isGuestUser($_SESSION['user'] ?? null)) {
        return;
    }

    $ticketId = (int)$ticketId;
    if ($ticketId < 1) {
        return;
    }

    $ids = getGuestTicketIds();
    $ids[] = $ticketId;
    $_SESSION['guest_ticket_ids'] = array_values(array_unique(array_map('intval', $ids)));
}

function guestOwnsTicket($ticketId)
{
    return in_array((int)$ticketId, getGuestTicketIds(), true);
}

function appUrl($target = '')
{
    $target = trim((string)$target);
    if ($target === '' || $target === './' || $target === '/' || $target === 'index' || $target === 'index.php' || $target === './index.php') {
        return '/';
    }

    $target = str_replace('\\', '/', $target);
    $parts = parse_url($target);
    if ($parts === false) {
        return '/';
    }

    $path = trim((string)($parts['path'] ?? ''), '/');
    $query = isset($parts['query']) ? (string)$parts['query'] : '';
    $fragment = isset($parts['fragment']) ? (string)$parts['fragment'] : '';
    $cleanPath = ltrim($path, './');

    if ($cleanPath === '' || $cleanPath === 'index' || $cleanPath === 'index.php') {
        $url = '/';
    } else {
        $segments = array_values(array_filter(explode('/', $cleanPath), 'strlen'));
        if (!empty($segments)) {
            $last = array_pop($segments);
            $last = preg_replace('/\.php$/i', '', $last);
            if ($last !== '' && $last !== 'index') {
                $segments[] = $last;
            }
        }

        $url = '/' . implode('/', $segments) . '/';
        if ($url === '//') {
            $url = '/';
        }
    }

    if ($query !== '') {
        $url .= '?' . $query;
    }

    if ($fragment !== '') {
        $url .= '#' . $fragment;
    }

    return $url;
}

function sanitizeRedirectTarget($target, $default = './mobile-home.php', $allowEmpty = false)
{
    $default = appUrl($default);
    $target = trim((string)$target);
    if ($target === '') {
        return $allowEmpty ? '' : $default;
    }

    $target = str_replace('\\', '/', $target);

    if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $target)) {
        return $default;
    }

    if (strpos($target, '//') === 0 || strpos($target, '..') !== false) {
        return $default;
    }

    if ($target[0] === '/') {
        $target = ltrim($target, '/');
    }

    $parts = parse_url($target);
    if ($parts === false) {
        return $default;
    }

    $path = trim((string)($parts['path'] ?? ''), '/');
    $filename = $path === '' ? 'index.php' : basename($path);
    if ($filename === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $filename)) {
        return $default;
    }

    if (!preg_match('/\.php$/i', $filename)) {
        $filename .= '.php';
    }

    $blockedPages = ['header.php', 'footer.php'];
    $appRoot = dirname(__DIR__);
    if (in_array($filename, $blockedPages, true) || !is_file($appRoot . DIRECTORY_SEPARATOR . $filename)) {
        return $default;
    }

    $normalizedTarget = appUrl($filename);
    if (!empty($parts['query'])) {
        $normalizedTarget .= '?' . $parts['query'];
    }
    if (!empty($parts['fragment'])) {
        $normalizedTarget .= '#' . $parts['fragment'];
    }

    return $normalizedTarget;
}

function currentRequestTarget($default = './mobile-home.php')
{
    $requestUri = trim((string)($_SERVER['REQUEST_URI'] ?? ''));
    if ($requestUri === '') {
        return appUrl($default);
    }

    $parts = parse_url($requestUri);
    if ($parts === false) {
        return appUrl($default);
    }

    $target = ltrim((string)($parts['path'] ?? ''), '/');
    if (!empty($parts['query'])) {
        $target .= '?' . $parts['query'];
    }
    if (!empty($parts['fragment'])) {
        $target .= '#' . $parts['fragment'];
    }

    return sanitizeRedirectTarget($target, $default);
}

function buildLoginUrl($redirect = '', $guestRequired = false)
{
    $params = [];
    $safeRedirect = sanitizeRedirectTarget($redirect, '', true);

    if ($safeRedirect !== '') {
        $params['redirect'] = ltrim($safeRedirect, './');
    }

    if ($guestRequired) {
        $params['guest_required'] = '1';
    }

    $query = http_build_query($params);

    return appUrl('index.php') . ($query !== '' ? '?' . $query : '');
}

function buildRegisterUrl($redirect = '')
{
    $safeRedirect = sanitizeRedirectTarget($redirect, '', true);
    if ($safeRedirect === '') {
        return appUrl('new.php');
    }

    return appUrl('new.php') . '?redirect=' . urlencode(ltrim($safeRedirect, './'));
}
