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

function buildGuestUser()
{
    $guest = new stdClass();
    $guest->id = 0;
    $guest->name = 'Guest';
    $guest->email = '';
    $guest->role = 'guest';

    return $guest;
}

function isGuestUser($user)
{
    return is_object($user) && (($user->role ?? '') === 'guest');
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
