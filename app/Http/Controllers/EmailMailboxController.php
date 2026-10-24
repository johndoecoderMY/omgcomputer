<?php

namespace App\Http\Controllers;

use App\Models\UserEmailMailbox;
use App\Services\EmailMailboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;
use RuntimeException;

class EmailMailboxController extends Controller
{
    public function index(Request $request, EmailMailboxService $mailbox): View
    {
        $messages = [];
        $error = session('mailbox_error');

        try {
            $messages = $mailbox->messages($request->user());
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        }

        return view('email.inbox', compact('messages', 'error'));
    }

    public function poll(Request $request, EmailMailboxService $mailbox): JsonResponse
    {
        try {
            return response()->json([
                'messages' => $mailbox->messages($request->user()),
                'checked_at' => now()->toIso8601String(),
            ])->header('Cache-Control', 'no-store, private');
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422)
                ->header('Cache-Control', 'no-store, private');
        }
    }

    public function compose(Request $request): View
    {
        return view('email.compose', [
            'mailbox' => $request->user()->emailMailbox,
        ]);
    }

    public function send(Request $request, EmailMailboxService $mailbox): RedirectResponse
    {
        $data = $request->validate([
            'recipients' => ['required', 'array', 'min:1', 'max:50'],
            'recipients.*' => ['required', 'email', 'max:255', 'distinct:ignore_case'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:100000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['required', File::types(['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'txt', 'csv', 'doc', 'docx', 'xls', 'xlsx', 'zip'])->max(10240)],
        ]);

        try {
            $mailbox->send(
                $request->user(),
                $data['recipients'],
                $data['subject'],
                $data['body'],
                $data['attachments'] ?? [],
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('compose_error', $exception->getMessage());
        }

        return redirect()->route('email.inbox')->with('mailbox_success', 'Email sent successfully.');
    }

    public function mailboxSettings(Request $request): View
    {
        return view('email.mailbox-settings', [
            'mailbox' => $request->user()->emailMailbox,
        ]);
    }

    public function updateMailboxSettings(Request $request): RedirectResponse
    {
        $mailbox = $request->user()->emailMailbox;
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => [Rule::requiredIf(! $mailbox), 'nullable', 'string', 'max:1000'],
            'signature' => ['nullable', 'string', 'max:5000'],
        ]);

        $mailbox ??= new UserEmailMailbox;
        $mailbox->email = $data['email'];
        $mailbox->signature = $data['signature'] ?? null;
        if (filled($data['password'] ?? null)) {
            $mailbox->password = $data['password'];
        }
        $request->user()->emailMailbox()->save($mailbox);

        return back()->with('mailbox_settings_success', 'Your mailbox connection was saved. The password is encrypted and never displayed.');
    }

    public function testMailboxConnection(Request $request, EmailMailboxService $mailbox): RedirectResponse
    {
        try {
            $mailbox->testConnection($request->user());
        } catch (RuntimeException $exception) {
            return back()->with('mailbox_settings_error', $exception->getMessage());
        }

        return back()->with('mailbox_settings_success', 'Connected to your mailbox successfully.');
    }

    public function deleteMailboxSettings(Request $request): RedirectResponse
    {
        $request->user()->emailMailbox()->delete();

        return redirect()->route('email.mailbox-settings')->with('mailbox_settings_success', 'Your mailbox connection was removed.');
    }

    public function show(Request $request, int $uid, EmailMailboxService $mailbox): View|RedirectResponse
    {
        try {
            $message = $mailbox->message($request->user(), $uid);
        } catch (RuntimeException $exception) {
            return redirect()->route('email.inbox')->with('mailbox_error', $exception->getMessage());
        }

        return view('email.show', compact('message'));
    }

    public function action(Request $request, int $uid, string $action, EmailMailboxService $mailbox): RedirectResponse
    {
        abort_unless(in_array($action, ['archive', 'delete', 'read', 'unread', 'flag', 'unflag'], true), 404);

        try {
            $mailbox->updateMessage($request->user(), $uid, $action);
        } catch (RuntimeException $exception) {
            return back()->with('mailbox_error', $exception->getMessage());
        }

        return redirect()->route('email.inbox')->with('mailbox_success', match ($action) {
            'archive' => 'Email archived.',
            'delete' => 'Email permanently deleted from the mailbox.',
            'read' => 'Email marked as read.',
            'unread' => 'Email marked as unread.',
            'flag' => 'Email flagged.',
            'unflag' => 'Email flag removed.',
        });
    }

    public function bulkAction(Request $request, EmailMailboxService $mailbox): RedirectResponse
    {
        $data = $request->validate([
            'uids' => ['required', 'array', 'min:1', 'max:50'],
            'uids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'action' => ['required', Rule::in(['archive', 'delete', 'read', 'unread', 'flag', 'unflag'])],
        ]);

        $uids = array_map(static fn (string|int $uid): int => (int) $uid, $data['uids']);

        try {
            $mailbox->updateMessages($request->user(), $uids, $data['action']);
        } catch (RuntimeException $exception) {
            return redirect()->route('email.inbox')->with('mailbox_error', $exception->getMessage());
        }

        return redirect()->route('email.inbox')->with('mailbox_success', count($uids).' email(s) updated.');
    }
}
