<?php

require_once __DIR__ . '/team.php';
require_once __DIR__ . '/team-member.php';

const SERVICE_REQUEST_META_START = '---SERVICE-REQUEST-META---';
const SERVICE_REQUEST_META_END = '---END-SERVICE-REQUEST-META---';

function service_request_categories(): array
{
    return [
        'roads' => [
            'label' => 'Roads and footpaths',
            'description' => 'Potholes, damaged footpaths, blocked access roads, unsafe crossings.',
            'keywords' => ['road', 'infrastructure', 'engineering', 'construction', 'public works'],
        ],
        'water' => [
            'label' => 'Water and sanitation',
            'description' => 'Drinking water issues, drainage, sewer overflow, public tap concerns.',
            'keywords' => ['water', 'sanitation', 'drinking water', 'drain', 'supply'],
        ],
        'waste' => [
            'label' => 'Waste collection and cleaning',
            'description' => 'Missed garbage pickup, dumping, street cleaning, public hygiene.',
            'keywords' => ['waste', 'clean', 'sanitation', 'environment'],
        ],
        'electricity' => [
            'label' => 'Street lights and electricity',
            'description' => 'Broken street lights, exposed wiring, dark public spaces.',
            'keywords' => ['electric', 'light', 'energy', 'street light'],
        ],
        'documents' => [
            'label' => 'Documents and ward services',
            'description' => 'Recommendation letters, certificates, document delays, counter support.',
            'keywords' => ['document', 'certificate', 'recommendation', 'ward', 'registration', 'administration'],
        ],
        'public-safety' => [
            'label' => 'Public safety',
            'description' => 'Unsafe public areas, damaged railings, urgent hazards, community risk.',
            'keywords' => ['safety', 'security', 'risk', 'hazard', 'emergency'],
        ],
        'social-support' => [
            'label' => 'Community and social support',
            'description' => 'Support for vulnerable residents, local program access, welfare concerns.',
            'keywords' => ['social', 'community', 'welfare', 'support', 'health'],
        ],
        'other' => [
            'label' => 'Other municipal service',
            'description' => 'Anything else that should reach the municipality.',
            'keywords' => ['support', 'service', 'office'],
        ],
    ];
}

function service_request_urgency_options(): array
{
    return [
        'normal' => 'Normal',
        'soon' => 'Needs attention this week',
        'urgent' => 'Urgent public impact',
    ];
}

function service_request_contact_windows(): array
{
    return [
        'anytime' => 'Anytime during office hours',
        'morning' => 'Morning (10:00 to 12:00)',
        'afternoon' => 'Afternoon (12:00 to 15:00)',
        'late' => 'Late afternoon (15:00 to 17:00)',
        'phone-only' => 'Phone call only',
    ];
}

function service_request_status_map(): array
{
    return [
        'open' => ['label' => 'Submitted', 'class' => 'badge-primary', 'summary' => 'Waiting for municipal review'],
        'pending' => ['label' => 'In progress', 'class' => 'badge-warning', 'summary' => 'Being reviewed or worked on'],
        'solved' => ['label' => 'Resolved', 'class' => 'badge-success', 'summary' => 'Work completed or answer provided'],
        'closed' => ['label' => 'Closed', 'class' => 'badge-secondary', 'summary' => 'Case archived'],
    ];
}

function service_request_priority_map(): array
{
    return [
        'low' => ['label' => 'Routine', 'class' => 'badge-light'],
        'medium' => ['label' => 'Priority', 'class' => 'badge-info'],
        'high' => ['label' => 'Urgent', 'class' => 'badge-danger'],
    ];
}

function service_request_priority_from_urgency(string $urgency): string
{
    $urgency = strtolower(trim($urgency));
    if ($urgency === 'urgent') {
        return 'high';
    }
    if ($urgency === 'soon') {
        return 'medium';
    }

    return 'low';
}

function service_request_extract_meta(string $body): array
{
    $body = (string)$body;
    $pattern = '/^' . preg_quote(SERVICE_REQUEST_META_START, '/') . '\R(.*?)\R' . preg_quote(SERVICE_REQUEST_META_END, '/') . '\R?/s';
    if (!preg_match($pattern, $body, $matches)) {
        return [];
    }

    $decoded = json_decode(trim($matches[1]), true);
    return is_array($decoded) ? $decoded : [];
}

function service_request_body_text(string $body): string
{
    $body = (string)$body;
    $pattern = '/^' . preg_quote(SERVICE_REQUEST_META_START, '/') . '\R.*?\R' . preg_quote(SERVICE_REQUEST_META_END, '/') . '\R?/s';
    $clean = preg_replace($pattern, '', $body, 1);
    return trim((string)$clean);
}

function service_request_build_body(array $meta, string $details): string
{
    $safeMeta = [
        'category' => trim((string)($meta['category'] ?? 'other')),
        'location' => trim((string)($meta['location'] ?? '')),
        'urgency' => trim((string)($meta['urgency'] ?? 'normal')),
        'contact_window' => trim((string)($meta['contact_window'] ?? 'anytime')),
        'reference_hint' => trim((string)($meta['reference_hint'] ?? '')),
    ];

    $json = json_encode($safeMeta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        $json = '{}';
    }

    return SERVICE_REQUEST_META_START . "\n" . $json . "\n" . SERVICE_REQUEST_META_END . "\n\n" . trim($details);
}

function service_request_case_number($ticket): string
{
    $id = (int)($ticket->id ?? 0);
    $createdAt = trim((string)($ticket->created_at ?? ''));
    $stamp = date('Ymd');
    if ($createdAt !== '') {
        try {
            $stamp = (new DateTime($createdAt))->format('Ymd');
        } catch (Throwable $e) {
            $stamp = date('Ymd');
        }
    }

    return sprintf('SG-%s-%04d', $stamp, max(1, $id));
}

function service_request_status_label(string $status): string
{
    $map = service_request_status_map();
    return $map[$status]['label'] ?? ucfirst($status);
}

function service_request_status_summary(string $status): string
{
    $map = service_request_status_map();
    return $map[$status]['summary'] ?? '';
}

function service_request_status_badge(string $status): string
{
    $map = service_request_status_map();
    $config = $map[$status] ?? ['label' => ucfirst($status), 'class' => 'badge-secondary'];
    return '<span class="badge ' . htmlspecialchars($config['class'], ENT_QUOTES, 'UTF-8') . '">' .
        htmlspecialchars($config['label'], ENT_QUOTES, 'UTF-8') .
        '</span>';
}

function service_request_priority_badge(string $priority): string
{
    $map = service_request_priority_map();
    $config = $map[$priority] ?? ['label' => ucfirst($priority), 'class' => 'badge-light'];
    return '<span class="badge ' . htmlspecialchars($config['class'], ENT_QUOTES, 'UTF-8') . '">' .
        htmlspecialchars($config['label'], ENT_QUOTES, 'UTF-8') .
        '</span>';
}

function service_request_category_label(string $category): string
{
    $categories = service_request_categories();
    return $categories[$category]['label'] ?? 'Other municipal service';
}

function service_request_contact_window_label(string $value): string
{
    $options = service_request_contact_windows();
    return $options[$value] ?? $options['anytime'];
}

function service_request_urgency_label(string $value): string
{
    $options = service_request_urgency_options();
    return $options[$value] ?? $options['normal'];
}

function service_request_ticket_data($ticket): array
{
    $meta = service_request_extract_meta((string)($ticket->body ?? ''));
    $details = service_request_body_text((string)($ticket->body ?? ''));

    return [
        'case_number' => service_request_case_number($ticket),
        'category' => $meta['category'] ?? 'other',
        'category_label' => service_request_category_label((string)($meta['category'] ?? 'other')),
        'location' => trim((string)($meta['location'] ?? '')),
        'urgency' => trim((string)($meta['urgency'] ?? 'normal')),
        'urgency_label' => service_request_urgency_label((string)($meta['urgency'] ?? 'normal')),
        'contact_window' => trim((string)($meta['contact_window'] ?? 'anytime')),
        'contact_window_label' => service_request_contact_window_label((string)($meta['contact_window'] ?? 'anytime')),
        'reference_hint' => trim((string)($meta['reference_hint'] ?? '')),
        'details' => $details,
        'status_label' => service_request_status_label((string)($ticket->status ?? 'open')),
        'status_summary' => service_request_status_summary((string)($ticket->status ?? 'open')),
        'priority_label' => service_request_priority_map()[$ticket->priority ?? 'low']['label'] ?? ucfirst((string)($ticket->priority ?? 'low')),
    ];
}

function service_request_route_team_id(string $category, array $teams): ?int
{
    $categories = service_request_categories();
    $keywords = $categories[$category]['keywords'] ?? [];

    foreach ($teams as $team) {
        $name = strtolower(trim((string)($team->name ?? '')));
        foreach ($keywords as $keyword) {
            if ($name !== '' && strpos($name, strtolower($keyword)) !== false) {
                return (int)$team->id;
            }
        }
    }

    return null;
}

function service_request_actor_name(int $actorId, $requesterOwner = null): string
{
    if ($actorId > 0) {
        $name = TeamMember::getName($actorId);
        if ($name !== '') {
            return $name;
        }
    }

    $fallback = trim((string)($requesterOwner->name ?? ''));
    return $fallback !== '' ? $fallback : 'Resident';
}

function service_request_status_options(): array
{
    return [
        'open' => 'Submitted',
        'pending' => 'In progress',
        'solved' => 'Resolved',
        'closed' => 'Closed',
    ];
}

function service_request_priority_options(): array
{
    return [
        'low' => 'Routine',
        'medium' => 'Priority',
        'high' => 'Urgent',
    ];
}

function service_request_admin_page_config(string $mode): array
{
    $config = [
        'all' => [
            'title' => 'Service Request Board',
            'subtitle' => 'All resident cases across the municipality.',
            'filter' => null,
            'unassigned' => false,
        ],
        'open' => [
            'title' => 'Submitted Cases',
            'subtitle' => 'New cases waiting for triage.',
            'filter' => 'open',
            'unassigned' => false,
        ],
        'pending' => [
            'title' => 'In-Progress Cases',
            'subtitle' => 'Cases currently being reviewed or worked on.',
            'filter' => 'pending',
            'unassigned' => false,
        ],
        'solved' => [
            'title' => 'Resolved Cases',
            'subtitle' => 'Cases with an answer or completed work.',
            'filter' => 'solved',
            'unassigned' => false,
        ],
        'closed' => [
            'title' => 'Closed Cases',
            'subtitle' => 'Archived or finished cases.',
            'filter' => 'closed',
            'unassigned' => false,
        ],
        'unassigned' => [
            'title' => 'Unassigned Cases',
            'subtitle' => 'Cases that still need a responsible team member.',
            'filter' => null,
            'unassigned' => true,
        ],
    ];

    return $config[$mode] ?? $config['all'];
}
