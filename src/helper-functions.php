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

function sanitizeRedirectTarget($target, $default = './mobile-home.php', $allowEmpty = false)
{
    $target = trim((string)$target);
    if ($target === '') {
        return $allowEmpty ? '' : $default;
    }

    $target = str_replace('\\', '/', $target);

    if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $target)) {
        return $default;
    }

    if (strpos($target, '//') === 0 || strpos($target, '..') !== false || $target[0] === '/') {
        return $default;
    }

    $path = parse_url($target, PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        return $default;
    }

    $filename = basename($path);
    $allowedPages = [
        'mobile-home.php',
        'tickets-menu.php',
        'ticket.php',
        'index.php',
        'new.php',
        'general-info-menu.php',
        'interesting-places.php',
        'municipality-introduction.php',
        'contacts.php',
        'documents-info.php'
    ];

    if (!in_array($filename, $allowedPages, true)) {
        return $default;
    }

    return './' . ltrim($target, './');
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

    return './index.php' . ($query !== '' ? '?' . $query : '');
}

function buildRegisterUrl($redirect = '')
{
    $safeRedirect = sanitizeRedirectTarget($redirect, '', true);
    if ($safeRedirect === '') {
        return './new.php';
    }

    return './new.php?redirect=' . urlencode(ltrim($safeRedirect, './'));
}
