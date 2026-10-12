<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ExpensesExport;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\Installation;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class ExpenseController extends Controller
{
    public function export(Request $request)
    {
        return Excel::download(
            new ExpensesExport($request->only('category')),
            'cheltuieli-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function index(Request $request): Response
    {
        $expenses = Expense::query()
            ->with(['supplier:id,name', 'installation:id,report_number,client_id'])
            ->when($request->string('category')->toString(), fn ($query, $category) => $query->where('category', $category))
            ->latest('expense_date')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Expenses/Index', [
            'expenses' => $expenses,
            'filters' => $request->only('category'),
            'summary' => [
                'total' => (float) Expense::sum('amount'),
                'this_month' => (float) Expense::whereBetween('expense_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Expenses/Create', [
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'installations' => Installation::with('client:id,name')->latest()->get(['id', 'client_id', 'report_number']),
        ]);
    }

    public function edit(Expense $expense): Response
    {
        return Inertia::render('Admin/Expenses/Edit', [
            'expense' => $expense,
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'installations' => Installation::with('client:id,name')->latest()->get(['id', 'client_id', 'report_number']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $expense = Expense::create($this->validateData($request));
        $this->storeDocument($request, $expense);
        AuditLog::record($request->user(), 'expense.created', "Cheltuiala a fost inregistrata: {$expense->description}.", $expense, ['amount' => (float) $expense->amount, 'category' => $expense->category]);

        return redirect()->route('admin.expenses.index')->with('success', 'Cheltuiala a fost inregistrata.');
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $expense->update($this->validateData($request));
        $this->storeDocument($request, $expense);
        AuditLog::record($request->user(), 'expense.updated', "Cheltuiala a fost actualizata: {$expense->description}.", $expense, ['amount' => (float) $expense->amount, 'category' => $expense->category]);

        return redirect()->route('admin.expenses.index')->with('success', 'Cheltuiala a fost actualizata.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'installation_id' => ['nullable', 'exists:installations,id'],
            'description' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:material,transport,manopera,other'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        if ($expense->document_path) {
            Storage::disk('public')->delete($expense->document_path);
        }
        $expense->delete();
        AuditLog::record($request->user(), 'expense.deleted', "Cheltuiala a fost stearsa: {$expense->description}.", null, ['expense_id' => $expense->id, 'amount' => (float) $expense->amount]);

        return back()->with('success', 'Cheltuiala a fost stearsa.');
    }

    private function storeDocument(Request $request, Expense $expense): void
    {
        if (! $request->hasFile('document')) {
            return;
        }

        if ($expense->document_path) {
            Storage::disk('public')->delete($expense->document_path);
        }

        $expense->update([
            'document_path' => $request->file('document')->store('expense-documents', 'public'),
        ]);
    }
}
