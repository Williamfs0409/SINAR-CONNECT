@php
    $adminFeature = [
        'pendaftaran' => ['title' => 'Pendaftaran Siswa Baru', 'description' => 'Catat pendaftaran dan tinjau siswa yang menunggu konfirmasi.'],
        'siswa' => ['title' => 'Data Siswa', 'description' => 'Satu daftar terpusat untuk data dan status seluruh siswa.'],
        'transaksi' => ['title' => 'SPP & Tabungan', 'description' => 'Buat tagihan, catat pembayaran SPP, dan kelola transaksi tabungan.'],
        'tunggakan' => ['title' => 'Tunggakan SPP', 'description' => 'Daftar tagihan yang belum lunas dan sudah melewati tanggal jatuh tempo.'],
        'pengguna' => ['title' => 'Pengguna & Hak Akses', 'description' => 'Buat akun dan tetapkan hak akses sesuai peran pengguna.'],
    ][$adminSection] ?? null;

    if ($adminFeature === null) {
        abort(404);
    }
@endphp

<nav class="admin-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a><span>›</span>{{ $adminFeature['title'] }}</nav>
<header class="admin-feature-heading">
    <div><h1>{{ $adminFeature['title'] }}</h1><p>{{ $adminFeature['description'] }}</p></div>
    <span>Administrator · SINAR</span>
</header>

@if (session('status'))
    <p class="admin-alert admin-alert-success" role="status">{{ session('status') }}</p>
@endif
@if ($errors->any())
    <div class="admin-alert admin-alert-error" role="alert">
        <strong>Periksa kembali data yang dimasukkan.</strong>
        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

@if ($adminSection === 'pendaftaran')
    <div class="admin-feature-grid">
        <section class="admin-data-panel">
            <div class="admin-data-heading"><div><h2>Formulir Pendaftaran</h2><p>Data akan tersimpan dengan status menunggu.</p></div></div>
            <form class="admin-form" method="POST" action="{{ route('admin.students.store') }}">
                @csrf
                <label>Nama siswa<input name="name" value="{{ old('name') }}" required maxlength="150"></label>
                <label>Nama orang tua / wali<input name="guardian_name" value="{{ old('guardian_name') }}" required maxlength="150"></label>
                <label>Nomor telepon wali<input name="guardian_phone" value="{{ old('guardian_phone') }}" maxlength="30"></label>
                <label>Kelompok yang dituju<input name="class_group" value="{{ old('class_group', 'Belum ditentukan') }}" required maxlength="80"></label>
                <button class="button button-primary" type="submit">Simpan pendaftaran</button>
            </form>
        </section>
        <section class="admin-data-panel">
            <div class="admin-data-heading"><div><h2>Menunggu Konfirmasi</h2><p>{{ $adminDashboardData['students']->count() }} pendaftaran</p></div></div>
            @if ($adminDashboardData['students']->isEmpty())
                <p class="admin-empty">Belum ada pendaftaran yang menunggu.</p>
            @else
                <div class="admin-record-list">
                    @foreach ($adminDashboardData['students'] as $student)
                        <article class="admin-record">
                            <div><strong>{{ $student->name }}</strong><small>{{ $student->registration_number }} · Wali: {{ $student->guardian_name }}</small></div>
                            <form method="POST" action="{{ route('admin.students.activate', $student) }}">@csrf<button class="admin-small-button" type="submit">Terima siswa</button></form>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@elseif ($adminSection === 'siswa')
    <section class="admin-data-panel">
        <div class="admin-data-heading">
            <div><h2>Daftar Siswa</h2><p>{{ $adminDashboardData['students']->count() }} data siswa terdaftar</p></div>
            <a class="button button-primary" href="{{ route('dashboard.feature', ['section' => 'pendaftaran']) }}">＋ Pendaftaran baru</a>
        </div>
        <div class="admin-student-list">
            @forelse ($adminDashboardData['students'] as $student)
                <form class="admin-student-edit" method="POST" action="{{ route('admin.students.update', $student) }}">
                    @csrf @method('PATCH')
                    <div class="admin-student-edit-meta"><strong>{{ $student->registration_number }}</strong><span class="admin-status {{ $student->status === 'active' ? 'is-active' : 'is-pending' }}">{{ $student->status === 'active' ? 'Aktif' : 'Menunggu' }}</span></div>
                    <label>Nama siswa<input name="name" value="{{ $student->name }}" required maxlength="150"></label>
                    <label>Orang tua / wali<input name="guardian_name" value="{{ $student->guardian_name }}" required maxlength="150"></label>
                    <label>Nomor telepon<input name="guardian_phone" value="{{ $student->guardian_phone }}" maxlength="30"></label>
                    <label>Kelompok<input name="class_group" value="{{ $student->class_group }}" required maxlength="80"></label>
                    <label>Status<select name="status"><option value="active" @selected($student->status === 'active')>Aktif</option><option value="pending" @selected($student->status === 'pending')>Menunggu</option></select></label>
                    <button class="admin-small-button" type="submit">Simpan perubahan</button>
                </form>
            @empty
                <p class="admin-empty">Belum ada data siswa. Mulai dari formulir pendaftaran.</p>
            @endforelse
        </div>
    </section>
@elseif ($adminSection === 'transaksi')
    <div class="admin-feature-grid admin-finance-grid">
        <section class="admin-data-panel">
            <div class="admin-data-heading"><div><h2>Buat Tagihan SPP</h2><p>Tagihan bulanan untuk siswa aktif.</p></div></div>
            <form class="admin-form" method="POST" action="{{ route('admin.invoices.store') }}">
                @csrf
                <label>Siswa<select name="student_id" required><option value="">Pilih siswa</option>@foreach ($adminDashboardData['students'] as $student)<option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>{{ $student->name }} · {{ $student->class_group }}</option>@endforeach</select></label>
                <label>Bulan tagihan<input type="month" name="period" value="{{ old('period', now('Asia/Jakarta')->format('Y-m')) }}" required></label>
                <label>Jumlah tagihan (Rp)<input type="number" name="amount" min="1" step="1" value="{{ old('amount', 250000) }}" required></label>
                <label>Tanggal jatuh tempo<input type="date" name="due_date" value="{{ old('due_date', now('Asia/Jakarta')->endOfMonth()->toDateString()) }}" required></label>
                <button class="button button-primary" type="submit">Buat tagihan</button>
            </form>
        </section>
        <section class="admin-data-panel">
            <div class="admin-data-heading"><div><h2>Catat Pembayaran SPP</h2><p>Terima pembayaran tunai atau transfer.</p></div></div>
            <form class="admin-form" method="POST" action="{{ route('admin.payments.store') }}">
                @csrf
                <label>Tagihan<select name="invoice_id" required><option value="">Pilih tagihan belum lunas</option>@foreach ($adminDashboardData['invoices'] as $invoice)<option value="{{ $invoice->id }}" @selected(old('invoice_id') == $invoice->id)>{{ $invoice->student->name }} · {{ $invoice->period_month->translatedFormat('F Y') }} · Sisa Rp{{ number_format($invoice->amount - $invoice->amount_paid, 0, ',', '.') }}</option>@endforeach</select></label>
                <label>Jumlah pembayaran (Rp)<input type="number" name="amount" min="1" step="1" value="{{ old('amount') }}" required></label>
                <label>Metode<select name="method" required><option value="cash">Tunai</option><option value="transfer">Transfer</option></select></label>
                <label>Tanggal pembayaran<input type="date" name="transaction_date" value="{{ old('transaction_date', now('Asia/Jakarta')->toDateString()) }}" required></label>
                <label>Catatan<input name="notes" value="{{ old('notes') }}" maxlength="500"></label>
                <button class="button button-primary" type="submit">Simpan pembayaran</button>
            </form>
        </section>
        <section class="admin-data-panel">
            <div class="admin-data-heading"><div><h2>Transaksi Tabungan</h2><p>Saldo seluruh siswa Rp{{ number_format($adminDashboardData['savingsBalance'], 0, ',', '.') }}</p></div></div>
            <form class="admin-form" method="POST" action="{{ route('admin.savings.store') }}">
                @csrf
                <label>Siswa<select name="student_id" required><option value="">Pilih siswa</option>@foreach ($adminDashboardData['students'] as $student)<option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>{{ $student->name }}</option>@endforeach</select></label>
                <label>Jenis transaksi<select name="direction" required><option value="deposit">Setoran</option><option value="withdrawal">Penarikan</option></select></label>
                <label>Jumlah (Rp)<input type="number" name="amount" min="1" step="1" value="{{ old('amount') }}" required></label>
                <label>Metode<select name="method" required><option value="cash">Tunai</option><option value="transfer">Transfer</option></select></label>
                <label>Tanggal transaksi<input type="date" name="transaction_date" value="{{ old('transaction_date', now('Asia/Jakarta')->toDateString()) }}" required></label>
                <label>Catatan<input name="notes" value="{{ old('notes') }}" maxlength="500"></label>
                <button class="button button-primary" type="submit">Simpan transaksi</button>
            </form>
        </section>
    </div>
    <section class="admin-data-panel admin-ledger-panel">
        <div class="admin-data-heading"><div><h2>Riwayat Transaksi</h2><p>50 transaksi terbaru · SPP bulan ini Rp{{ number_format($adminDashboardData['monthIncome'], 0, ',', '.') }}</p></div></div>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Tanggal</th><th>Siswa</th><th>Jenis</th><th>Metode</th><th>Jumlah</th><th>Catatan</th></tr></thead><tbody>
            @forelse ($adminDashboardData['transactions'] as $transaction)
                <tr><td>{{ $transaction->transaction_date->format('d/m/Y') }}</td><td>{{ $transaction->student->name }}</td><td>{{ $transaction->category === 'spp' ? 'Pembayaran SPP' : ($transaction->direction === 'deposit' ? 'Setoran tabungan' : 'Penarikan tabungan') }}</td><td>{{ $transaction->method === 'cash' ? 'Tunai' : 'Transfer' }}</td><td>Rp{{ number_format($transaction->amount, 0, ',', '.') }}</td><td>{{ $transaction->notes ?: '—' }}</td></tr>
            @empty
                <tr><td colspan="6" class="admin-empty">Belum ada transaksi yang tercatat.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>
@elseif ($adminSection === 'tunggakan')
    <section class="admin-data-panel">
        <div class="admin-data-heading"><div><h2>Tagihan Melewati Jatuh Tempo</h2><p>{{ $adminDashboardData['invoices']->count() }} tagihan belum lunas setelah tanggal jatuh tempo.</p></div><a class="button button-primary" href="{{ route('dashboard.feature', ['section' => 'transaksi']) }}">Buat / catat tagihan</a></div>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Siswa</th><th>Kelompok</th><th>Periode</th><th>Jatuh tempo</th><th>Total tagihan</th><th>Sudah dibayar</th><th>Sisa tunggakan</th><th>Kontak wali</th></tr></thead><tbody>
            @forelse ($adminDashboardData['invoices'] as $invoice)
                <tr><td><strong>{{ $invoice->student->name }}</strong></td><td>{{ $invoice->student->class_group }}</td><td>{{ $invoice->period_month->translatedFormat('F Y') }}</td><td>{{ $invoice->due_date->format('d/m/Y') }}</td><td>Rp{{ number_format($invoice->amount, 0, ',', '.') }}</td><td>Rp{{ number_format($invoice->amount_paid, 0, ',', '.') }}</td><td><strong class="admin-debt">Rp{{ number_format($invoice->amount - $invoice->amount_paid, 0, ',', '.') }}</strong></td><td>{{ $invoice->student->guardian_name }}<small>{{ $invoice->student->guardian_phone ?: 'Telepon belum dicatat' }}</small></td></tr>
            @empty
                <tr><td colspan="8" class="admin-empty">Tidak ada tunggakan SPP yang jatuh tempo saat ini.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>
@elseif ($adminSection === 'pengguna')
    <div class="admin-feature-grid">
        <section class="admin-data-panel">
            <div class="admin-data-heading"><div><h2>Buat Akun Pengguna</h2><p>Tetapkan peran yang sesuai dengan kebutuhan akses.</p></div></div>
            <form class="admin-form" method="POST" action="{{ route('admin.users.store') }}">
                @csrf
                <label>Nama pengguna<input name="name" value="{{ old('name') }}" required maxlength="150"></label>
                <label>Email<input type="email" name="email" value="{{ old('email') }}" required maxlength="255"></label>
                <label>Peran<select name="role" required>@foreach ($adminDashboardData['roles'] as $role)<option value="{{ $role }}" @selected(old('role', 'teacher') === $role)>{{ ['admin' => 'Administrator', 'teacher' => 'Guru', 'principal' => 'Kepala Sekolah', 'parent' => 'Orang Tua'][$role] }}</option>@endforeach</select></label>
                <label>Kata sandi
                    <span class="registration-password-wrap">
<<<<<<< HEAD
                        <input id="admin-user-password" type="password" name="password" required minlength="12" autocomplete="new-password">
=======
                        <input id="admin-user-password" type="password" name="password" required minlength="8" autocomplete="new-password">
>>>>>>> d177baab25805b47fc739fd939367501e00cd0bb
                        <button type="button" class="password-toggle" aria-label="Tampilkan kata sandi" aria-controls="admin-user-password" aria-pressed="false">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="2.7" stroke="currentColor" stroke-width="2"/>
                                <path class="password-eye-slash" d="m4 4 16 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </span>
                </label>
                <label>Konfirmasi kata sandi
                    <span class="registration-password-wrap">
<<<<<<< HEAD
                        <input id="admin-user-password-confirmation" type="password" name="password_confirmation" required minlength="12" autocomplete="new-password">
=======
                        <input id="admin-user-password-confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
>>>>>>> d177baab25805b47fc739fd939367501e00cd0bb
                        <button type="button" class="password-toggle" aria-label="Tampilkan kata sandi" aria-controls="admin-user-password-confirmation" aria-pressed="false">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="2.7" stroke="currentColor" stroke-width="2"/>
                                <path class="password-eye-slash" d="m4 4 16 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </span>
                </label>
                <button class="button button-primary" type="submit">Buat akun</button>
            </form>
        </section>
        <section class="admin-data-panel">
            <div class="admin-data-heading"><div><h2>Daftar Pengguna</h2><p>{{ $adminDashboardData['users']->count() }} akun terdaftar</p></div></div>
            <div class="admin-user-list">
                @foreach ($adminDashboardData['users'] as $user)
                    <article class="admin-user-row">
                        <div class="admin-user-name"><span>{{ mb_substr($user->name, 0, 1) }}</span><div><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></div></div>
                        <form class="admin-role-form" method="POST" action="{{ route('admin.users.role', $user) }}">
                            @csrf @method('PATCH')
                            <select name="role" aria-label="Peran {{ $user->name }}">@foreach ($adminDashboardData['roles'] as $role)<option value="{{ $role }}" @selected($user->role === $role)>{{ ['admin' => 'Administrator', 'teacher' => 'Guru', 'principal' => 'Kepala Sekolah', 'parent' => 'Orang Tua'][$role] }}</option>@endforeach</select>
                            <button type="submit">Simpan</button>
                        </form>
                    </article>
                @endforeach
            </div>
        </section>
    </div>
@endif
