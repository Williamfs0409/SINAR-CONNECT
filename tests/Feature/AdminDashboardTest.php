<?php

namespace Tests\Feature;

use App\Models\SchoolTransaction;
use App\Models\SppInvoice;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_features_are_restricted_to_administrator_accounts(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($teacher)
            ->get(route('dashboard.feature', ['section' => 'pendaftaran']))
            ->assertNotFound();

        $this->post(route('admin.students.store'), [
            'name' => 'Calon Siswa',
            'guardian_name' => 'Orang Tua',
            'class_group' => 'A1',
        ])->assertForbidden();
    }

    public function test_unknown_account_role_cannot_fall_back_to_the_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'unknown']);

        $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertForbidden();
    }

    public function test_admin_can_open_each_data_management_page(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Siswa Aktif');

        foreach ([
        'pendaftaran' => 'Pendaftaran Siswa Baru',
        'siswa' => 'Daftar Siswa',
        'transaksi' => 'Catat Pembayaran SPP',
        'tunggakan' => 'Tagihan Melewati Jatuh Tempo',
        'pengguna' => 'Buat Akun Pengguna',
        ] as $section => $heading) {
        $this->get(route('dashboard.feature', ['section' => $section]))
            ->assertOk()
            ->assertSee($heading);
        }
    }

    public function test_admin_can_register_and_activate_a_student(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.students.store'), [
                'name' => 'Nadia Putri',
                'guardian_name' => 'Dina Putri',
                'guardian_phone' => '08123456789',
                'class_group' => 'A1 · Melati',
            ])
            ->assertRedirect(route('dashboard.feature', ['section' => 'pendaftaran']));

        $student = Student::firstOrFail();
        $this->assertSame('pending', $student->status);
        $this->assertStringStartsWith('SIS-', $student->registration_number);

        $this->post(route('admin.students.activate', $student))
            ->assertRedirect();
        $this->assertDatabaseHas('students', ['id' => $student->id, 'status' => 'active']);

        $this->get(route('dashboard.feature', ['section' => 'siswa']))
            ->assertOk()
            ->assertSee('Nadia Putri')
            ->assertSee('Aktif');

        $this->patch(route('admin.students.update', $student), [
            'name' => 'Nadia Putri Lestari',
            'guardian_name' => 'Dina Putri',
            'guardian_phone' => '08123456789',
            'class_group' => 'A2 · Mawar',
            'status' => 'active',
        ])->assertRedirect(route('dashboard.feature', ['section' => 'siswa']));

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'name' => 'Nadia Putri Lestari',
            'class_group' => 'A2 · Mawar',
        ]);
    }

    public function test_spp_payments_update_invoice_balance_and_tunggakan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::create([
            'registration_number' => 'SIS-00001',
            'name' => 'Nadia Putri',
            'guardian_name' => 'Dina Putri',
            'class_group' => 'A1',
            'status' => 'active',
        ]);
        $invoice = SppInvoice::create([
            'student_id' => $student->id,
            'period_month' => now()->subMonth()->startOfMonth()->toDateString(),
            'amount' => 250000,
            'amount_paid' => 0,
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.payments.store'), [
                'invoice_id' => $invoice->id,
                'amount' => 100000,
                'method' => 'transfer',
                'transaction_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('dashboard.feature', ['section' => 'transaksi']));

        $this->assertDatabaseHas('spp_invoices', ['id' => $invoice->id, 'amount_paid' => 100000]);
        $this->assertDatabaseHas('school_transactions', [
            'invoice_id' => $invoice->id,
            'category' => 'spp',
            'method' => 'transfer',
            'amount' => 100000,
        ]);
        $this->get(route('dashboard.feature', ['section' => 'tunggakan']))
            ->assertOk()
            ->assertSee('Rp150.000');

        $this->from(route('dashboard.feature', ['section' => 'transaksi']))
            ->post(route('admin.payments.store'), [
                'invoice_id' => $invoice->id,
                'amount' => 200000,
                'method' => 'cash',
                'transaction_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('amount');

        $this->post(route('admin.payments.store'), [
            'invoice_id' => $invoice->id,
            'amount' => 150000,
            'method' => 'cash',
            'transaction_date' => now()->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('spp_invoices', ['id' => $invoice->id, 'amount_paid' => 250000]);
        $this->assertSame(2, SchoolTransaction::where('invoice_id', $invoice->id)->count());
    }

    public function test_admin_can_create_one_monthly_spp_invoice_per_student(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::create([
            'registration_number' => 'SIS-00003',
            'name' => 'Bima Putra',
            'guardian_name' => 'Orang Tua Bima',
            'class_group' => 'B1',
            'status' => 'active',
        ]);
        $invoiceInput = [
            'student_id' => $student->id,
            'period' => now()->format('Y-m'),
            'amount' => 250000,
            'due_date' => now()->addDays(10)->toDateString(),
        ];

        $this->actingAs($admin)
            ->post(route('admin.invoices.store'), $invoiceInput)
            ->assertRedirect(route('dashboard.feature', ['section' => 'transaksi']));

        $this->assertDatabaseHas('spp_invoices', [
            'student_id' => $student->id,
            'period_month' => now()->startOfMonth()->toDateString(),
            'amount' => 250000,
        ]);

        $this->from(route('dashboard.feature', ['section' => 'transaksi']))
            ->post(route('admin.invoices.store'), $invoiceInput)
            ->assertSessionHasErrors('period');
        $this->assertSame(1, SppInvoice::where('student_id', $student->id)->count());
    }

    public function test_savings_transactions_reject_withdrawals_over_the_student_balance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::create([
            'registration_number' => 'SIS-00002',
            'name' => 'Rafi Pratama',
            'guardian_name' => 'Orang Tua Rafi',
            'class_group' => 'A2',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.savings.store'), [
                'student_id' => $student->id,
                'direction' => 'deposit',
                'amount' => 50000,
                'method' => 'cash',
                'transaction_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->from(route('dashboard.feature', ['section' => 'transaksi']))
            ->post(route('admin.savings.store'), [
                'student_id' => $student->id,
                'direction' => 'withdrawal',
                'amount' => 60000,
                'method' => 'transfer',
                'transaction_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame(1, SchoolTransaction::where('category', 'savings')->count());
        $this->get(route('dashboard.feature', ['section' => 'transaksi']))
            ->assertOk()
            ->assertSee('Rp50.000');
    }

    public function test_admin_can_create_accounts_and_manage_roles_without_demoting_last_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Dewi Guru',
                'email' => 'dewi@example.com',
                'role' => 'teacher',
                'password' => 'strong-password',
                'password_confirmation' => 'strong-password',
            ])
            ->assertRedirect(route('dashboard.feature', ['section' => 'pengguna']));

        $teacher = User::where('email', 'dewi@example.com')->firstOrFail();
        $this->assertSame('teacher', $teacher->role);
        $this->assertTrue(Hash::check('strong-password', $teacher->password));

        $this->patch(route('admin.users.role', $teacher), ['role' => 'principal'])
            ->assertRedirect();
        $this->assertDatabaseHas('users', ['id_user' => $teacher->id_user, 'role' => 'kepala_sekolah']);

        $this->from(route('dashboard.feature', ['section' => 'pengguna']))
            ->patch(route('admin.users.role', $admin), ['role' => 'teacher'])
            ->assertSessionHasErrors('role');
        $this->assertDatabaseHas('users', ['id_user' => $admin->id_user, 'role' => 'admin']);
    }
}
