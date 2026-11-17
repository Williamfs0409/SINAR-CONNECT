<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\SchoolFeatureController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('/login', 'welcome')->name('login');
Route::post('/login', LoginController::class)->name('login.authenticate');
Route::get('/pendaftaran', [RegistrationController::class, 'create'])->name('registration.create');
Route::post('/pendaftaran', [RegistrationController::class, 'store'])->name('registration.store');
Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');
Route::get('/dashboard/{section}', [DashboardController::class, 'show'])
    ->where('section', '[a-z-]+')
    ->name('dashboard.feature');
Route::middleware('auth')->group(function () {
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profil/foto', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::get('/profil/foto/{user}', [ProfileController::class, 'photo'])->name('profile.photo');
    Route::get('/pesan', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/pesan', [MessageController::class, 'store'])->name('messages.store');
    Route::get('/pesan/notifikasi', [MessageController::class, 'notifications'])
        ->name('messages.notifications');
    Route::patch('/pesan/notifikasi/baca-semua', [MessageController::class, 'markAllNotificationsRead'])
        ->name('messages.notifications.read-all');

    Route::patch('/dashboard/siswa/{student}/profil', [SchoolFeatureController::class, 'updateStudentProfile'])
        ->name('database.student.profile');
    Route::patch('/dashboard/keluarga/{family}/profil', [SchoolFeatureController::class, 'updateFamilyProfile'])
        ->name('database.family.update');
    Route::patch('/dashboard/pengguna/{user}/profil', [SchoolFeatureController::class, 'updateUserProfile'])
        ->name('database.user.profile.update');
    Route::delete('/dashboard/siswa/{student}', [SchoolFeatureController::class, 'destroyStudent'])
        ->name('database.student.destroy');
    Route::delete('/dashboard/keluarga/{family}', [SchoolFeatureController::class, 'destroyFamily'])
        ->name('database.family.destroy');
    Route::delete('/dashboard/pengguna/{user}', [SchoolFeatureController::class, 'destroyUser'])
        ->name('database.user.destroy');
    Route::patch('/dashboard/tarif/{tariff}', [SchoolFeatureController::class, 'updateTariff'])
        ->name('database.tariff.update');
    Route::post('/dashboard/tarif', [SchoolFeatureController::class, 'storeTariff'])
        ->name('database.tariff.store');
    Route::post('/dashboard/pendaftaran/{registration}/status', [SchoolFeatureController::class, 'reviewRegistration'])
        ->name('database.registration.review');
    Route::post('/dashboard/pendaftaran/{registration}/dokumen', [SchoolFeatureController::class, 'uploadRegistrationDocuments'])
        ->name('database.registration.documents.upload');
    Route::post('/dashboard/dokumen-pendaftaran/{document}/verifikasi', [SchoolFeatureController::class, 'verifyRegistrationDocument'])
        ->name('database.registration.document');
    Route::get('/dashboard/dokumen-pendaftaran/{document}/file', [SchoolFeatureController::class, 'registrationDocument'])
        ->name('database.registration.document.file');
    Route::post('/dashboard/absensi', [SchoolFeatureController::class, 'storeAttendance'])
        ->name('database.attendance.store');
    Route::post('/dashboard/rencana-pembelajaran', [SchoolFeatureController::class, 'storeLessonPlan'])
        ->name('database.lesson-plan.store');
    Route::post('/dashboard/tugas', [SchoolFeatureController::class, 'storeTask'])
        ->name('database.task.store');
    Route::post('/dashboard/tugas/{task}/pengumpulan', [SchoolFeatureController::class, 'submitTask'])
        ->name('database.task.submit');
    Route::patch('/dashboard/pengumpulan/{submission}/nilai', [SchoolFeatureController::class, 'gradeTask'])
        ->name('database.task.grade');
    Route::get('/dashboard/pengumpulan/{submission}/file', [SchoolFeatureController::class, 'taskSubmissionFile'])
        ->name('database.task.submission.file');
    Route::get('/dashboard/tugas/{task}/lampiran', [SchoolFeatureController::class, 'taskAttachment'])
        ->name('database.task.attachment');
    Route::post('/dashboard/nilai-iqro', [SchoolFeatureController::class, 'storeIqroGrade'])
        ->name('database.iqro.store');
    Route::post('/dashboard/rapor', [SchoolFeatureController::class, 'storeReport'])
        ->name('database.report.store');
    Route::patch('/dashboard/rapor/{report}/refleksi', [SchoolFeatureController::class, 'updateReportReflection'])
        ->name('database.report.reflection');
    Route::post('/dashboard/portofolio', [SchoolFeatureController::class, 'storePortfolio'])
        ->name('database.portfolio.store');
    Route::get('/dashboard/portofolio/{portfolio}/foto', [SchoolFeatureController::class, 'portfolioPhoto'])
        ->name('database.portfolio.photo');
    Route::post('/dashboard/kegiatan', [SchoolFeatureController::class, 'storeActivity'])
        ->name('database.activity.store');
    Route::post('/dashboard/kegiatan/{activity}/foto', [SchoolFeatureController::class, 'uploadActivityPhoto'])
        ->name('database.activity.photo.store');
    Route::get('/dashboard/dokumentasi/{document}/foto', [SchoolFeatureController::class, 'activityPhoto'])
        ->name('database.activity.photo');
    Route::post('/dashboard/pembayaran', [SchoolFeatureController::class, 'storePayment'])
        ->name('database.payment.store');
    Route::patch('/dashboard/pembayaran/rekening', [SchoolFeatureController::class, 'updatePaymentSettings'])
        ->name('database.payment-settings.update');
    Route::patch('/dashboard/pembayaran/{payment}/verifikasi', [SchoolFeatureController::class, 'reviewPayment'])
        ->name('database.payment.review');
    Route::get('/dashboard/pembayaran/{payment}/bukti', [SchoolFeatureController::class, 'paymentProof'])
        ->name('database.payment.proof');
    Route::post('/dashboard/tabungan', [SchoolFeatureController::class, 'storeSavings'])
        ->name('database.savings.store');
    Route::post('/dashboard/tagihan/spp', [SchoolFeatureController::class, 'generateMonthlyBills'])
        ->name('database.bills.generate');
    Route::post('/dashboard/tagihan/siswa', [SchoolFeatureController::class, 'createStudentBill'])
        ->name('database.bills.store');
    Route::post('/dashboard/keluarga/tautkan', [SchoolFeatureController::class, 'linkParentToFamily'])
        ->name('database.family.link-parent');
});
Route::middleware('auth')->group(function () {
    Route::post('/admin/students', [AdminDashboardController::class, 'storeStudent'])
        ->name('admin.students.store');
    Route::post('/admin/students/{student}/activate', [AdminDashboardController::class, 'activateStudent'])
        ->name('admin.students.activate');
    Route::patch('/admin/students/{student}', [AdminDashboardController::class, 'updateStudent'])
        ->name('admin.students.update');
    Route::post('/admin/invoices', [AdminDashboardController::class, 'createInvoice'])
        ->name('admin.invoices.store');
    Route::post('/admin/payments', [AdminDashboardController::class, 'recordSppPayment'])
        ->name('admin.payments.store');
    Route::post('/admin/savings', [AdminDashboardController::class, 'recordSavings'])
        ->name('admin.savings.store');
    Route::post('/admin/users', [AdminDashboardController::class, 'createUser'])
        ->name('admin.users.store');
    Route::patch('/admin/users/{user}/role', [AdminDashboardController::class, 'updateUserRole'])
        ->name('admin.users.role');
});
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('home');
})->middleware('auth')->name('logout');
