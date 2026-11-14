<?php

namespace App\Http\Controllers;

use App\Models\SchoolTransaction;
use App\Models\SppInvoice;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminDashboardController extends Controller
{
    private const USER_ROLES = ['admin', 'teacher', 'principal', 'parent'];

    public function show(string $section = 'ringkasan'): View
    {
        $this->authorizeAdministrator();

        $sections = ['ringkasan', 'pendaftaran', 'siswa', 'transaksi', 'tunggakan', 'pengguna'];
        abort_unless(in_array($section, $sections, true), 404);

        $data = match ($section) {
            'ringkasan' => [
                'studentCount' => Student::where('status', 'active')->count(),
                'pendingCount' => Student::where('status', 'pending')->count(),
                'userCount' => User::count(),
                'monthIncome' => SchoolTransaction::where('category', 'spp')
                    ->whereMonth('transaction_date', now('Asia/Jakarta')->month)
                    ->whereYear('transaction_date', now('Asia/Jakarta')->year)
                    ->sum('amount'),
                'arrearsCount' => SppInvoice::whereColumn('amount_paid', '<', 'amount')
                    ->whereDate('due_date', '<', now('Asia/Jakarta')->toDateString())
                    ->count(),
            ],
            'pendaftaran' => [
                'students' => Student::where('status', 'pending')->latest()->get(),
            ],
            'siswa' => [
                'students' => Student::latest()->get(),
            ],
            'transaksi' => [
                'students' => Student::where('status', 'active')->orderBy('name')->get(),
                'invoices' => SppInvoice::with('student')
                    ->whereColumn('amount_paid', '<', 'amount')
                    ->orderBy('due_date')
                    ->get(),
                'transactions' => SchoolTransaction::with(['student', 'invoice'])
                    ->latest('transaction_date')
                    ->latest('id')
                    ->limit(50)
                    ->get(),
                'savingsBalance' => $this->savingsBalance(),
                'monthIncome' => SchoolTransaction::where('category', 'spp')
                    ->whereMonth('transaction_date', now('Asia/Jakarta')->month)
                    ->whereYear('transaction_date', now('Asia/Jakarta')->year)
                    ->sum('amount'),
            ],
            'tunggakan' => [
                'students' => Student::where('status', 'active')->orderBy('name')->get(),
                'invoices' => SppInvoice::with('student')
                    ->whereColumn('amount_paid', '<', 'amount')
                    ->whereDate('due_date', '<', now('Asia/Jakarta')->toDateString())
                    ->orderBy('due_date')
                    ->get(),
            ],
            'pengguna' => [
                'users' => User::orderBy('name')->get(),
                'roles' => self::USER_ROLES,
            ],
        };

        return view('dashboard', [
            'section' => $section,
            'adminSection' => $section,
            'adminDashboardData' => $data,
        ]);
    }

    public function storeStudent(Request $request): RedirectResponse
    {
        $this->authorizeAdministrator();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'guardian_name' => ['required', 'string', 'max:150'],
            'guardian_phone' => ['nullable', 'string', 'max:30'],
            'class_group' => ['required', 'string', 'max:80'],
        ]);

        DB::transaction(function () use ($data): void {
            $student = Student::create([
                ...$data,
                'registration_number' => 'NEW-'.Str::upper(Str::random(10)),
                'status' => 'pending',
            ]);
            $student->update([
                'registration_number' => 'SIS-'.str_pad((string) $student->id, 5, '0', STR_PAD_LEFT),
            ]);
        });

        return redirect()->route('dashboard.feature', ['section' => 'pendaftaran'])
            ->with('status', 'Pendaftaran siswa berhasil disimpan.');
    }

    public function activateStudent(Student $student): RedirectResponse
    {
        $this->authorizeAdministrator();

        $student->update(['status' => 'active']);

        return back()->with('status', 'Data siswa berhasil diaktifkan.');
    }

    public function updateStudent(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeAdministrator();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'guardian_name' => ['required', 'string', 'max:150'],
            'guardian_phone' => ['nullable', 'string', 'max:30'],
            'class_group' => ['required', 'string', 'max:80'],
            'status' => ['required', Rule::in(['pending', 'active'])],
        ]);

        $student->update($data);

        return redirect()->route('dashboard.feature', ['section' => 'siswa'])
            ->with('status', 'Data siswa berhasil diperbarui.');
    }

    public function createInvoice(Request $request): RedirectResponse
    {
        $this->authorizeAdministrator();

        $data = $request->validate([
            'student_id' => ['required', Rule::exists('students', 'id')->where(fn (Builder $query) => $query->where('status', 'active'))],
            'period' => ['required', 'date_format:Y-m'],
            'amount' => ['required', 'integer', 'min:1', 'max:100000000'],
            'due_date' => ['required', 'date'],
        ]);

        $periodMonth = Carbon::createFromFormat('!Y-m', $data['period'])->startOfMonth()->toDateString();
        $existingInvoice = SppInvoice::where('student_id', $data['student_id'])
            ->whereDate('period_month', $periodMonth)
            ->exists();

        if ($existingInvoice) {
            throw ValidationException::withMessages([
                'period' => 'Tagihan untuk siswa dan bulan tersebut sudah ada.',
            ]);
        }

        SppInvoice::create([
            'student_id' => $data['student_id'],
            'period_month' => $periodMonth,
            'amount' => $data['amount'],
            'amount_paid' => 0,
            'due_date' => $data['due_date'],
        ]);

        return redirect()->route('dashboard.feature', ['section' => 'transaksi'])
            ->with('status', 'Tagihan SPP berhasil dibuat.');
    }

    public function recordSppPayment(Request $request): RedirectResponse
    {
        $this->authorizeAdministrator();

        $data = $request->validate([
            'invoice_id' => ['required', 'exists:spp_invoices,id'],
            'amount' => ['required', 'integer', 'min:1', 'max:100000000'],
            'method' => ['required', Rule::in(['cash', 'transfer'])],
            'transaction_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($data): void {
            $invoice = SppInvoice::whereKey($data['invoice_id'])->lockForUpdate()->firstOrFail();
            $outstanding = $invoice->amount - $invoice->amount_paid;

            if ($data['amount'] > $outstanding) {
                throw ValidationException::withMessages([
                    'amount' => 'Pembayaran melebihi sisa tagihan SPP.',
                ]);
            }

            SchoolTransaction::create([
                'student_id' => $invoice->student_id,
                'invoice_id' => $invoice->id,
                'category' => 'spp',
                'direction' => 'payment',
                'method' => $data['method'],
                'amount' => $data['amount'],
                'transaction_date' => $data['transaction_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            $invoice->increment('amount_paid', $data['amount']);
        });

        return redirect()->route('dashboard.feature', ['section' => 'transaksi'])
            ->with('status', 'Pembayaran SPP berhasil dicatat.');
    }

    public function recordSavings(Request $request): RedirectResponse
    {
        $this->authorizeAdministrator();

        $data = $request->validate([
            'student_id' => ['required', Rule::exists('students', 'id')->where(fn (Builder $query) => $query->where('status', 'active'))],
            'direction' => ['required', Rule::in(['deposit', 'withdrawal'])],
            'amount' => ['required', 'integer', 'min:1', 'max:100000000'],
            'method' => ['required', Rule::in(['cash', 'transfer'])],
            'transaction_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($data): void {
            Student::whereKey($data['student_id'])->lockForUpdate()->firstOrFail();
            $balance = $this->savingsBalance((int) $data['student_id']);

            if ($data['direction'] === 'withdrawal' && $data['amount'] > $balance) {
                throw ValidationException::withMessages([
                    'amount' => 'Saldo tabungan siswa tidak mencukupi.',
                ]);
            }

            SchoolTransaction::create([
                'student_id' => $data['student_id'],
                'category' => 'savings',
                'direction' => $data['direction'],
                'method' => $data['method'],
                'amount' => $data['amount'],
                'transaction_date' => $data['transaction_date'],
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return redirect()->route('dashboard.feature', ['section' => 'transaksi'])
            ->with('status', 'Transaksi tabungan berhasil dicatat.');
    }

    public function createUser(Request $request): RedirectResponse
    {
        $this->authorizeAdministrator();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(self::USER_ROLES)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'password' => $data['password'],
        ]);

        return redirect()->route('dashboard.feature', ['section' => 'pengguna'])
            ->with('status', 'Akun pengguna berhasil dibuat.');
    }

    public function updateUserRole(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdministrator();

        $data = $request->validate([
            'role' => ['required', Rule::in(self::USER_ROLES)],
        ]);

        if ($user->is(auth()->user()) && $data['role'] !== 'admin') {
            throw ValidationException::withMessages([
                'role' => 'Peran administrator yang sedang digunakan tidak dapat diubah.',
            ]);
        }

        if ($user->role === 'admin' && $data['role'] !== 'admin'
            && User::where('role', 'admin')->count() <= 1) {
            throw ValidationException::withMessages([
                'role' => 'Minimal harus ada satu akun administrator.',
            ]);
        }

        $user->update(['role' => $data['role']]);

        return redirect()->route('dashboard.feature', ['section' => 'pengguna'])
            ->with('status', 'Hak akses pengguna berhasil diperbarui.');
    }

    private function savingsBalance(?int $studentId = null): int
    {
        $transactions = SchoolTransaction::where('category', 'savings');

        if ($studentId !== null) {
            $transactions->where('student_id', $studentId);
        }

        return (int) $transactions->selectRaw(
            'COALESCE(SUM(CASE WHEN direction = ? THEN amount ELSE -amount END), 0) as balance',
            ['deposit']
        )->value('balance');
    }

    private function authorizeAdministrator(): void
    {
        abort_unless(auth()->check() && auth()->user()->role === 'admin', 403);
    }
}
