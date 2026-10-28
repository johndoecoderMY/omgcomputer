<?php

namespace App\Http\Middleware;

use App\Models\Attachment;
use App\Models\BusinessExpense;
use App\Models\CustomerMessageTemplate;
use App\Models\Event;
use App\Models\Project;
use App\Models\RepairTicket;
use App\Models\RepairTicketUpdate;
use App\Models\Task;
use App\Models\User;
use App\Support\WorkspacePermissions;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspacePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        if (in_array((string) $request->route()?->getName(), [
            'customer.contact',
            'email.inbox',
            'email.poll',
            'email.compose',
            'email.send',
            'email.show',
            'email.action',
            'email.bulk-action',
            'email.mailbox-settings',
            'email.mailbox-settings.update',
            'email.mailbox-settings.test',
            'email.mailbox-settings.delete',
            'billing.index',
            'billing.datatable',
            'billing.documents.show',
            'billing.documents.pdf',
            'billing.chip.checkout',
            'billing.chip.return',
            'shop.chip.return',
            'shop.panel',
            'shop.panel.products.show',
            'shop.tracking',
            'shop.tracking.lookup',
            'shop.my-orders.index',
            'shop.my-orders.show',
            'shop.my-orders.tracking',
        ], true)) {
            return $next($request);
        }

        if ($user->hasRole('customer') && in_array((string) $request->route()?->getName(), [
            'shop.products.purchase',
            'shop.cart',
            'shop.cart.add',
            'shop.cart.update',
            'shop.cart.remove',
            'shop.checkout',
            'shop.checkout.place',
        ], true)) {
            return $next($request);
        }

        $permission = $this->permissionForRoute($request);
        if (! $permission || ! $this->userHasPermission($user, $permission)) {
            abort(403);
        }

        $this->authorizeOwnership($request, $user, $permission);

        return $next($request);
    }

    private function permissionForRoute(Request $request): ?string
    {
        $name = (string) $request->route()?->getName();

        if (in_array($name, ['dashboard'], true)) {
            return 'dashboard.view';
        }
        if (str_starts_with($name, 'notifications.') || str_starts_with($name, 'profile.')) {
            return 'profile.manage_own';
        }
        if (in_array($name, ['calendar', 'calendar.events'], true)) {
            return 'projects.calendar.manage';
        }
        if (str_starts_with($name, 'projects.')) {
            if (in_array($name, ['projects.index', 'projects.datatable', 'projects.show', 'projects.calendar.events'], true)) {
                return 'projects.view';
            }
            if ($name === 'projects.calendar') {
                return 'projects.calendar.manage';
            }
            if (in_array($name, ['projects.create', 'projects.store'], true)) {
                return 'projects.create';
            }
            if ($name === 'projects.destroy') {
                return 'projects.delete';
            }
            if ($name === 'projects.export') {
                return 'projects.export';
            }
            if (str_contains($name, 'calendar') || str_contains($name, 'events')) {
                return 'projects.calendar.manage';
            }
            if (str_contains($name, '.tasks.')) {
                return str_ends_with($name, '.tasks.create') || str_ends_with($name, '.tasks.store')
                    ? 'tasks.create'
                    : 'tasks.view';
            }

            return 'projects.update';
        }
        if (str_starts_with($name, 'tasks.')) {
            if (in_array($name, ['tasks.index', 'tasks.datatable', 'tasks.kanban', 'tasks.show'], true)) {
                return 'tasks.view';
            }
            if (in_array($name, ['tasks.create', 'tasks.store', 'tasks.subtasks.store'], true)) {
                return 'tasks.create';
            }
            if ($name === 'tasks.destroy') {
                return 'tasks.delete';
            }
            if ($name === 'tasks.update-status') {
                return 'tasks.status.update';
            }

            return 'tasks.update';
        }
        if (str_starts_with($name, 'tickets.')) {
            return in_array($name, ['tickets.index', 'tickets.datatable', 'tickets.show'], true)
                ? ($request->user()->hasRole('customer') ? 'tickets.view' : 'tickets.manage')
                : 'tickets.manage';
        }
        if (str_starts_with($name, 'bookings.')) {
            if (in_array($name, ['bookings.account-create', 'bookings.account-store'], true)) {
                return 'bookings.view';
            }
            if (in_array($name, ['bookings.index', 'bookings.datatable', 'bookings.show', 'bookings.calendar', 'bookings.calendar.events'], true)) {
                return 'bookings.view';
            }

            return 'bookings.manage';
        }
        if (str_starts_with($name, 'booking-time-slots.')) {
            return 'booking_availability.manage';
        }
        if (str_starts_with($name, 'customers.')) {
            return 'customers.manage';
        }
        if (str_starts_with($name, 'customer-messages.')) {
            if (in_array($name, ['customer-messages.index', 'customer-messages.datatable', 'customer-messages.show'], true)) {
                return 'customer_messages.view';
            }
            if (in_array($name, ['customer-messages.create', 'customer-messages.store'], true)) {
                return 'customer_messages.create';
            }

            return 'customer_messages.manage_own';
        }
        if (str_starts_with($name, 'billing.')) {
            if ($request->user()->hasRole('customer')
                && ! in_array($name, ['billing.index', 'billing.datatable', 'billing.documents.show', 'billing.documents.pdf'], true)) {
                return null;
            }

            return in_array($name, ['billing.index', 'billing.datatable', 'billing.documents.show', 'billing.documents.pdf'], true)
                ? 'billing.view'
                : 'billing.manage';
        }
        if (str_starts_with($name, 'products.')) {
            return in_array($name, ['products.index', 'products.show'], true) ? 'products.view' : 'products.manage';
        }
        if (str_starts_with($name, 'development.flowcharts.')) {
            return in_array($name, ['development.flowcharts.index', 'development.flowcharts.show', 'development.flowcharts.datatable'], true)
                ? 'flowcharts.view'
                : 'flowcharts.manage';
        }
        if (str_starts_with($name, 'meta-content.')) {
            return in_array($name, ['meta-content.index'], true) ? 'meta_content.view' : 'meta_content.manage';
        }
        if (str_starts_with($name, 'drama-content.')) {
            return 'drama_content.manage';
        }
        if (str_starts_with($name, 'inventory.') || str_starts_with($name, 'inventory.suppliers.')) {
            return in_array($name, ['inventory.index', 'inventory.parts', 'inventory.parts.datatable', 'inventory.movements', 'inventory.movements.datatable', 'inventory.suppliers.index', 'inventory.suppliers.datatable', 'inventory.suppliers.show', 'inventory.parts.show'], true)
                ? 'inventory.view'
                : 'inventory.manage';
        }
        if (str_starts_with($name, 'reports.')) {
            return 'reports.view';
        }
        if (str_starts_with($name, 'admin.backups.')) {
            return 'backups.manage';
        }
        if (str_starts_with($name, 'reports.integrations.') || str_starts_with($name, 'marketplace-shops.')) {
            return 'integrations.manage';
        }
        if (str_starts_with($name, 'expenses.')) {
            return match ($request->route('category')) {
                'rental' => 'rental.manage',
                'bills' => 'bills.manage',
                'services' => 'services.manage',
                default => null,
            };
        }
        if ($name === 'development.sops' || $name === 'development.sops.datatable') {
            return 'sops.view';
        }
        if (str_starts_with($name, 'development.sops.')) {
            return 'sops.manage';
        }
        if (in_array($name, ['development.overview'], true)) {
            return 'company.overview.view';
        }
        if (in_array($name, ['development.plan'], true)) {
            return 'business_plan.view';
        }
        if (str_starts_with($name, 'teams.')) {
            return 'teams.manage';
        }
        if (str_starts_with($name, 'departments.')) {
            return 'departments.manage';
        }
        if (str_starts_with($name, 'admin.users.')) {
            return in_array($name, ['admin.users.index', 'admin.users.datatable'], true)
                ? 'users.view'
                : 'users.manage';
        }
        if ($name === 'members.show') {
            return 'users.view';
        }
        if ($name === 'error-pages.preview') {
            return 'error_pages.view';
        }
        if (str_starts_with($name, 'attachments.')) {
            return 'profile.manage_own';
        }
        if ($name === 'projects.attachments.store' || $name === 'projects.attachments.caption') {
            return 'projects.update';
        }
        if ($name === 'tasks.attachments.store' || $name === 'tasks.attachments.caption') {
            return 'tasks.update';
        }
        if ($name === 'tickets.attachments.store' || $name === 'tickets.steps.store' || $name === 'tickets.steps.attachments.store') {
            return $request->user()->hasRole('customer') ? null : 'tickets.manage';
        }
        if (str_starts_with($name, 'customer-messages.attachments.')) {
            return 'customer_messages.manage_own';
        }
        if ($name === 'events.store' || $name === 'events.destroy') {
            return 'projects.calendar.manage';
        }
        if (str_starts_with($name, 'admin.access.')) {
            return null;
        }

        return null;
    }

    private function userHasPermission(User $user, string $permission): bool
    {
        if (! $user->roles()->exists()) {
            return in_array($permission, WorkspacePermissions::MEMBER, true);
        }

        if (! $user->roles()->whereIn('name', ['admin', 'member', 'customer'])->exists()) {
            return false;
        }

        if (! Permission::query()->where('name', $permission)->where('guard_name', 'web')->exists()) {
            return false;
        }

        return $user->hasPermissionTo($permission);
    }

    private function authorizeOwnership(Request $request, User $user, string $permission): void
    {
        $route = $request->route();
        $project = $route?->parameter('project');
        $task = $route?->parameter('task');

        if ($project instanceof Project && in_array($permission, [
            'projects.update', 'projects.delete', 'projects.calendar.manage', 'projects.export',
        ], true)) {
            abort_unless(
                (int) $project->created_by_user_id === (int) $user->id
                    || $project->assignees()->whereKey($user->id)->exists(),
                403,
            );
        }
        if ($task instanceof Task && in_array($permission, ['tasks.update', 'tasks.delete', 'tasks.status.update'], true)) {
            abort_unless($this->ownsOrIsAssignedToTask($task, $user), 403);
        }
        if ($permission === 'projects.calendar.manage' && $route?->parameter('event') instanceof Event) {
            $eventProject = $route->parameter('event')->project;
            abort_unless($eventProject && $this->ownsOrIsAssignedToProject($eventProject, $user), 403);
        }
        if ($permission === 'projects.calendar.manage' && $request->filled('project_id')) {
            $eventProject = Project::query()->findOrFail($request->integer('project_id'));
            abort_unless($this->ownsOrIsAssignedToProject($eventProject, $user), 403);
        }
        if ($permission === 'projects.calendar.manage' && $route?->getName() === 'events.store') {
            abort_unless($request->filled('project_id'), 403);
        }
        if ($permission === 'tasks.create' && $task instanceof Task) {
            abort_unless($this->ownsOrIsAssignedToTask($task, $user), 403);
        }
        if ($permission === 'tasks.create' && $request->filled('parent_id')) {
            $parent = Task::query()->findOrFail($request->integer('parent_id'));
            abort_unless($this->ownsOrIsAssignedToTask($parent, $user), 403);
        }
        if ($permission === 'tasks.update' && $task instanceof Task && $request->filled('project_id')) {
            $projectId = $request->integer('project_id');
            if ($projectId !== (int) $task->project_id) {
                $targetProject = Project::query()->findOrFail($projectId);
                abort_unless($this->ownsOrIsAssignedToProject($targetProject, $user), 403);
            }
        }
        if ($permission === 'tasks.create') {
            $project = $route?->parameter('project');
            if (! $project instanceof Project && $request->filled('project_id')) {
                $project = Project::query()->findOrFail($request->integer('project_id'));
            }
            if ($project instanceof Project) {
                abort_unless($this->ownsOrIsAssignedToProject($project, $user), 403);
            }
        }
        if ($permission === 'customer_messages.manage_own') {
            $template = $route?->parameter('customerMessage');
            if ($template) {
                abort_unless((int) $template->created_by_user_id === (int) $user->id, 403);
            }
        }
        if ($route?->getName() === 'attachments.show') {
            $attachment = $route->parameter('attachment');
            abort_unless($attachment instanceof Attachment, 404);
            $attachable = $attachment->attachable;

            if ($attachable instanceof BusinessExpense) {
                abort(403);
            }

            if ($attachable instanceof Project) {
                abort_unless($this->userHasPermission($user, 'projects.view'), 403);
                abort_if($user->hasRole('customer'), 404);
            } elseif ($attachable instanceof Task) {
                abort_unless($this->userHasPermission($user, 'tasks.view'), 403);
            } elseif ($attachable instanceof RepairTicket) {
                $this->authorizeTicketAttachment($attachable, $user);
            } elseif ($attachable instanceof RepairTicketUpdate) {
                $this->authorizeTicketAttachment($attachable->ticket, $user);
            } elseif ($attachable instanceof CustomerMessageTemplate) {
                abort_unless($this->userHasPermission($user, 'customer_messages.view'), 403);
            } else {
                abort(403);
            }
        }
    }

    private function authorizeTicketAttachment(?RepairTicket $ticket, User $user): void
    {
        abort_unless($ticket, 404);

        if ($user->hasRole('customer')) {
            abort_unless(
                strtolower(trim((string) $ticket->customer()->value('email'))) === strtolower(trim($user->email)),
                404,
            );
        } else {
            abort_unless($this->userHasPermission($user, 'tickets.manage'), 403);
        }
    }

    private function ownsOrIsAssignedToProject(Project $project, User $user): bool
    {
        return (int) $project->created_by_user_id === (int) $user->id
            || $project->assignees()->whereKey($user->id)->exists();
    }

    private function ownsOrIsAssignedToTask(Task $task, User $user): bool
    {
        return (int) $task->created_by_user_id === (int) $user->id
            || (int) $task->assigned_to === (int) $user->id
            || $task->assignees()->whereKey($user->id)->exists();
    }
}
