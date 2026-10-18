@props(['status', 'label' => null])

{{ \App\Support\StatusBadge::make(
    (string) $status,
    $label ?? str_replace('_', ' ', ucfirst((string) $status)),
) }}
