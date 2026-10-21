<?php

namespace App\Http\Controllers;

use App\Models\BusinessExpense;
use App\Services\WorkspaceNotifier;
use App\Support\PdfAttachmentImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Mpdf\Mpdf;

class BusinessExpenseController extends Controller
{
    public function index(string $category): View
    {
        $this->ensureCategory($category);
        $expenses = BusinessExpense::query()
            ->where('category', $category)
            ->with(['payments.user', 'attachments'])
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->orderBy('name')
            ->get();

        return view('expenses.index', [
            'category' => $category,
            'categoryLabel' => BusinessExpense::CATEGORIES[$category],
            'expenses' => $expenses,
            'upcomingCount' => $expenses->filter(fn (BusinessExpense $expense): bool => $expense->remaining_amount > 0
                && $expense->due_date
                && $expense->due_date->between(today(), today()->addDays(30)))->count(),
            'overdueCount' => $expenses->filter(fn (BusinessExpense $expense): bool => $expense->payment_status === 'overdue')->count(),
        ]);
    }

    public function create(string $category): View
    {
        $this->ensureCategory($category);

        return view('expenses.form', [
            'category' => $category,
            'categoryLabel' => BusinessExpense::CATEGORIES[$category],
            'expense' => new BusinessExpense(['frequency' => 'once']),
            'formAction' => route('expenses.store', $category),
            'editing' => false,
        ]);
    }

    public function store(Request $request, string $category, WorkspaceNotifier $notifier): RedirectResponse
    {
        $this->ensureCategory($category);
        $data = $this->validateExpense($request);
        $data['category'] = $category;
        $data['user_id'] = $request->user()->id;
        $data['cycle_paid_amount'] = 0;
        $expense = BusinessExpense::query()->create($data);
        $notifier->notifyAdmins(
            $request->user(),
            BusinessExpense::CATEGORIES[$category].' added',
            sprintf('%s was added: %s.', BusinessExpense::CATEGORIES[$category], $expense->name),
            route('expenses.index', $category),
        );

        return redirect()->route('expenses.index', $category)->with('success', 'Expense or renewal added.');
    }

    public function edit(string $category, BusinessExpense $expense): View
    {
        $this->ensureExpenseCategory($category, $expense);

        return view('expenses.form', [
            'category' => $category,
            'categoryLabel' => BusinessExpense::CATEGORIES[$category],
            'expense' => $expense,
            'formAction' => route('expenses.update', [$category, $expense]),
            'editing' => true,
        ]);
    }

    public function update(Request $request, string $category, BusinessExpense $expense, WorkspaceNotifier $notifier): RedirectResponse
    {
        $this->ensureExpenseCategory($category, $expense);
        $data = $this->validateExpense($request);
        $expense->update($data);
        $notifier->notifyAdmins(
            $request->user(),
            BusinessExpense::CATEGORIES[$category].' updated',
            sprintf('%s was updated: %s.', BusinessExpense::CATEGORIES[$category], $expense->name),
            route('expenses.index', $category),
        );

        return redirect()->route('expenses.index', $category)->with('success', 'Expense or renewal updated.');
    }

    public function destroy(Request $request, string $category, BusinessExpense $expense, WorkspaceNotifier $notifier): RedirectResponse
    {
        $this->ensureExpenseCategory($category, $expense);
        $expenseName = $expense->name;
        $expense->delete();
        $notifier->notifyAdmins(
            $request->user(),
            BusinessExpense::CATEGORIES[$category].' removed',
            sprintf('%s was removed: %s.', BusinessExpense::CATEGORIES[$category], $expenseName),
            route('expenses.index', $category),
        );

        return redirect()->route('expenses.index', $category)->with('success', 'Expense or renewal deleted.');
    }

    public function storePayment(Request $request, string $category, BusinessExpense $expense, WorkspaceNotifier $notifier): RedirectResponse
    {
        $this->ensureExpenseCategory($category, $expense);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'paid_at' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($data, $expense, $request): void {
            $lockedExpense = BusinessExpense::query()->whereKey($expense->id)->lockForUpdate()->firstOrFail();
            $amount = round((float) $data['amount'], 2);
            if ($amount > $lockedExpense->remaining_amount) {
                throw ValidationException::withMessages([
                    'amount' => 'The payment cannot exceed the remaining amount due.',
                ]);
            }

            $lockedExpense->payments()->create([
                ...$data,
                'user_id' => $request->user()->id,
                'period_due_date' => $lockedExpense->due_date?->toDateString(),
                'amount' => $amount,
            ]);
            $lockedExpense->cycle_paid_amount = round((float) $lockedExpense->cycle_paid_amount + $amount, 2);

            if ($lockedExpense->remaining_amount <= 0 && $lockedExpense->frequency !== 'once') {
                $months = ['monthly' => 1, 'quarterly' => 3, 'yearly' => 12][$lockedExpense->frequency];
                $lockedExpense->due_date = $lockedExpense->due_date->copy()->addMonthsNoOverflow($months);
                $lockedExpense->cycle_paid_amount = 0;
            }

            $lockedExpense->save();
        });
        $notifier->notifyAdmins(
            $request->user(),
            BusinessExpense::CATEGORIES[$category].' payment recorded',
            sprintf('A payment was recorded for %s.', $expense->name),
            route('expenses.index', $category),
        );

        return redirect()->route('expenses.index', $category)->with('success', 'Payment recorded.');
    }

    public function pdf(string $category, BusinessExpense $expense, PdfAttachmentImage $attachmentImage)
    {
        $this->ensureExpenseCategory($category, $expense);
        $expense->load(['payments.user', 'attachments']);
        $photos = $expense->attachments
            ->filter(fn ($attachment): bool => str_starts_with($attachment->mime_type, 'image/'))
            ->map(fn ($attachment): array => $attachmentImage->make($attachment));
        $documents = $expense->attachments->where('mime_type', 'application/pdf');

        $tempDirectory = storage_path('app/mpdf-tmp');
        File::ensureDirectoryExists($tempDirectory);
        $mpdf = new Mpdf(['tempDir' => $tempDirectory]);
        $mpdf->AliasNbPages();
        $mpdf->SetFooter('|Page {PAGENO} of {nb}|');
        $mpdf->WriteHTML(view('expenses.pdf', compact('expense', 'photos'))->render());

        $filename = Str::slug($expense->name).'-'.Str::slug($category).'.pdf';

        return response($mpdf->Output($filename, 'S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.$filename.'"');
    }

    private function validateExpense(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'frequency' => ['required', Rule::in(array_keys(BusinessExpense::FREQUENCIES))],
            'due_date' => ['nullable', 'required_unless:frequency,once', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        return $data;
    }

    private function ensureCategory(string $category): void
    {
        abort_unless(array_key_exists($category, BusinessExpense::CATEGORIES), 404);
    }

    private function ensureExpenseCategory(string $category, BusinessExpense $expense): void
    {
        $this->ensureCategory($category);
        abort_unless($expense->category === $category, 404);
    }
}
