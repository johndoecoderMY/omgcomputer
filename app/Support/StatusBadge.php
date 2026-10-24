<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

class StatusBadge
{
    private const COLORS = [
        'active' => 'success',
        'accepted' => 'success',
        'archived' => 'secondary',
        'backlog' => 'secondary',
        'cancelled' => 'danger',
        'closed' => 'secondary',
        'completed' => 'success',
        'confirmed' => 'success',
        'configured' => 'success',
        'created' => 'info',
        'diagnosing' => 'info',
        'delivered' => 'success',
        'done' => 'success',
        'draft' => 'secondary',
        'disconnected' => 'secondary',
        'failed' => 'danger',
        'in_progress' => 'primary',
        'in_repair' => 'primary',
        'inactive' => 'secondary',
        'issued' => 'info',
        'in_cancel' => 'danger',
        'missing' => 'secondary',
        'new' => 'info',
        'not_connected' => 'secondary',
        'paid' => 'success',
        'part_paid' => 'warning',
        'partial' => 'warning',
        'pending' => 'warning',
        'processing' => 'primary',
        'ready_for_pickup' => 'success',
        'ready_to_ship' => 'info',
        'received' => 'info',
        'refund' => 'danger',
        'refunded' => 'danger',
        'rejected' => 'danger',
        'scheduled' => 'info',
        'sent' => 'info',
        'shipped' => 'primary',
        'published' => 'success',
        'to_receive' => 'info',
        'to_ship' => 'info',
        'unpaid' => 'warning',
        'void' => 'danger',
        'waiting_customer' => 'warning',
        'waiting_parts' => 'warning',
    ];

    public static function make(string $status, string $label): HtmlString
    {
        return new HtmlString(sprintf(
            '<span class="badge text-bg-%s">%s</span>',
            self::colorClass($status),
            e($label),
        ));
    }

    public static function colorClass(string $status): string
    {
        $key = strtolower(trim($status));
        $key = preg_replace('/[^a-z0-9]+/', '_', $key) ?? $key;

        return self::COLORS[trim($key, '_')] ?? 'secondary';
    }

    public static function pdfColor(string $status): string
    {
        return match (self::colorClass($status)) {
            'success' => '#198754',
            'primary' => '#0d6efd',
            'info' => '#0dcaf0',
            'warning' => '#ffc107',
            'danger' => '#dc3545',
            default => '#6c757d',
        };
    }

    public static function ganttPdfColor(string $status): string
    {
        return match (self::colorClass($status)) {
            'success' => '#00d27a',
            'primary' => '#f34303',
            'info' => '#27bcfd',
            'warning' => '#ffb51b',
            'danger' => '#e63757',
            'secondary' => '#ffb51b',
            default => '#ffb51b',
        };
    }
}
