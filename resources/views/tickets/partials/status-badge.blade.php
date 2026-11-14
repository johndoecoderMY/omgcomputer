<x-status-badge :status="$ticket->status" :label="$statuses[$ticket->status] ?? ucfirst($ticket->status)" />
