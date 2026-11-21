<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PasswordResetRequestsController;
use App\Http\Controllers\PasswordSecurityController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\SchoolDataController;
use App\Http\Controllers\SchoolFeatureController;
use App\Http\Middleware\EnsurePasswordChange;
use App\Http\Middleware\SetAuditUserContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('/login', 'welcome')->name('login');
Route::post('/login', LoginController::class)->middleware('throttle:5,1')->name('login.authenticate');
Route::get('/pendaftaran', [RegistrationController::class, 'create'])->name('registration.create');
Route::post('/pendaftaran', [RegistrationController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('registration.store');
Route::get('/pendaftaran/berhasil', [RegistrationController::class, 'success'])
    ->name('registration.success');
Route::get('/kata-sandi/lupa', [PasswordSecurityController::class, 'showRequestForm'])
    ->middleware('guest')
    ->name('password.request');
Route::post('/kata-sandi/email', [PasswordSecurityController::class, 'sendResetLink'])
    ->middleware(['guest', 'throttle:5,1'])
    ->name('password.email');
Route::get('/kata-sandi/menunggu-konfirmasi', [PasswordSecurityController::class, 'waitForAdministrator'])
    ->middleware('throttle:30,1')
    ->name('password.reset.waiting');
Route::get('/kata-sandi/buat-baru', [PasswordSecurityController::class, 'showOwnerResetForm'])
    ->middleware('throttle:30,1')
    ->name('password.owner-reset.form');
Route::post('/kata-sandi/buat-baru', [PasswordSecurityController::class, 'updateOwnerPassword'])
    ->middleware('throttle:5,1')
    ->name('password.owner-reset.update');
Route::get('/kata-sandi/reset/{token}', [PasswordSecurityController::class, 'showResetForm'])
    ->middleware('guest')
    ->name('password.reset');
Route::post('/kata-sandi/reset', [PasswordSecurityController::class, 'reset'])
    ->middleware(['guest', 'throttle:5,1'])
    ->name('password.update');
Route::middleware('auth')->group(function () {
    Route::get('/kata-sandi/wajib', [PasswordSecurityController::class, 'showForceChange'])
        ->name('password.force');
    Route::put('/kata-sandi/wajib', [PasswordSecurityController::class, 'forceChange'])
        ->name('password.force.update');
});
Route::get('/dashboard', [DashboardController::class, 'show'])
    ->middleware(EnsurePasswordChange::class)
    ->name('dashboard');
Route::get('/dashboard/cari-global', [SchoolDataController::class, 'globalSearch'])
    ->middleware(['auth', EnsurePasswordChange::class])
    ->name('dashboard.global-search');
Route::get('/dashboard/{section}', [DashboardController::class, 'show'])
    ->where('section', '[a-z-]+')
    ->middleware(EnsurePasswordChange::class)
    ->name('dashboard.feature');
Route::middleware(['auth', EnsurePasswordChange::class, SetAuditUserContext::class])->group(function () {
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profil/foto', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::get('/profil/foto/{user}', [ProfileController::class, 'photo'])->name('profile.photo');
    Route::get('/pesan', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/pesan', [MessageController::class, 'store'])->name('messages.store');
    Route::delete('/pesan/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
    Route::get('/pesan/thread/{contact}', [MessageController::class, 'thread'])->name('messages.thread');
    Route::get('/pesan/notifikasi', [MessageController::class, 'notifications'])
        ->name('messages.notifications');
    Route::patch('/pesan/notifikasi/baca-semua', [MessageController::class, 'markAllNotificationsRead'])
        ->name('messages.notifications.read-all');

    Route::post('/dashboard/siswa', [SchoolFeatureController::class, 'storeStudent'])
        ->name('database.student.store');
    Route::post('/dashboard/siswa/penempatan', [SchoolFeatureController::class, 'placeStudent'])
        ->name('database.student.place');
    Route::patch('/dashboard/siswa/{student}/profil', [SchoolFeatureController::class, 'updateStudentProfile'])
        ->name('database.student.profile');
    Route::post('/dashboard/keluarga', [SchoolFeatureController::class, 'storeFamily'])
        ->name('database.family.store');
    Route::patch('/dashboard/keluarga/{family}/profil', [SchoolFeatureController::class, 'updateFamilyProfile'])
        ->name('database.family.update');
    Route::post('/dashboard/guru', [SchoolFeatureController::class, 'storeTeacher'])
        ->name('database.teacher.store');
    Route::patch('/dashboard/guru/{teacher}', [SchoolFeatureController::class, 'updateTeacher'])
        ->name('database.teacher.update');
    Route::delete('/dashboard/guru/{teacher}', [SchoolFeatureController::class, 'destroyTeacher'])
        ->name('database.teacher.destroy');
    Route::post('/dashboard/guru/kelas', [SchoolFeatureController::class, 'assignTeacherClass'])
        ->name('database.teacher.assign-class');
    Route::post('/dashboard/kelas', [SchoolFeatureController::class, 'storeClass'])
        ->name('database.class.store');
    Route::patch('/dashboard/kelas/{schoolClass}', [SchoolFeatureController::class, 'updateClass'])
        ->name('database.class.update');
    Route::delete('/dashboard/kelas/{schoolClass}', [SchoolFeatureController::class, 'destroyClass'])
        ->name('database.class.destroy');
    Route::post('/dashboard/tahun-ajaran', [SchoolFeatureController::class, 'storeSchoolYear'])
        ->name('database.school-year.store');
    Route::post('/dashboard/duplikat-siswa/gabung', [SchoolFeatureController::class, 'mergeDuplicateStudents'])
        ->name('database.student.merge');
    Route::post('/dashboard/keluarga/gabung', [SchoolFeatureController::class, 'mergeDuplicateFamilies'])
        ->name('database.family.merge');
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
    Route::delete('/dashboard/tarif/{tariff}', [SchoolFeatureController::class, 'destroyTariff'])
        ->name('database.tariff.destroy');
    Route::post('/dashboard/tarif', [SchoolFeatureController::class, 'storeTariff'])
        ->name('database.tariff.store');
    Route::post('/dashboard/pendaftaran/{registration}/status', [SchoolFeatureController::class, 'reviewRegistration'])
        ->name('database.registration.review');
    Route::patch('/dashboard/pendaftaran/pengaturan', [SchoolFeatureController::class, 'updateRegistrationStatus'])
        ->name('database.registration.settings.update');
    Route::post('/dashboard/pendaftaran/{registration}/dokumen', [SchoolFeatureController::class, 'uploadRegistrationDocuments'])
        ->name('database.registration.documents.upload');
    Route::post('/dashboard/dokumen-pendaftaran/{document}/verifikasi', [SchoolFeatureController::class, 'verifyRegistrationDocument'])
        ->name('database.registration.document');
    Route::get('/dashboard/dokumen-pendaftaran/{document}/file', [SchoolFeatureController::class, 'registrationDocument'])
        ->name('database.registration.document.file');
    Route::post('/dashboard/absensi/inisialisasi', [SchoolFeatureController::class, 'initializeAttendance'])
        ->name('database.attendance.init');
    Route::post('/dashboard/absensi', [SchoolFeatureController::class, 'storeAttendance'])
        ->name('database.attendance.store');
    Route::post('/dashboard/rencana-pembelajaran', [SchoolFeatureController::class, 'storeLessonPlan'])
        ->name('database.lesson-plan.store');
    Route::patch('/dashboard/rencana-pembelajaran/{lessonPlan}', [SchoolFeatureController::class, 'updateLessonPlan'])
        ->name('database.lesson-plan.update');
    Route::delete('/dashboard/rencana-pembelajaran/{lessonPlan}', [SchoolFeatureController::class, 'destroyLessonPlan'])
        ->name('database.lesson-plan.destroy');
    Route::post('/dashboard/tugas', [SchoolFeatureController::class, 'storeTask'])
        ->name('database.task.store');
    Route::patch('/dashboard/tugas/{task}', [SchoolFeatureController::class, 'updateTask'])
        ->name('database.task.update');
    Route::delete('/dashboard/tugas/{task}', [SchoolFeatureController::class, 'destroyTask'])
        ->name('database.task.destroy');
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
    Route::patch('/dashboard/portofolio/{portfolio}', [SchoolFeatureController::class, 'updatePortfolio'])
        ->name('database.portfolio.update');
    Route::delete('/dashboard/portofolio/{portfolio}', [SchoolFeatureController::class, 'destroyPortfolio'])
        ->name('database.portfolio.destroy');
    Route::get('/dashboard/portofolio/{portfolio}/foto', [SchoolFeatureController::class, 'portfolioPhoto'])
        ->name('database.portfolio.photo');
    Route::post('/dashboard/kegiatan', [SchoolFeatureController::class, 'storeActivity'])
        ->name('database.activity.store');
    Route::patch('/dashboard/kegiatan/{activity}', [SchoolFeatureController::class, 'updateActivity'])
        ->name('database.activity.update');
    Route::delete('/dashboard/kegiatan/{activity}', [SchoolFeatureController::class, 'destroyActivity'])
        ->name('database.activity.destroy');
    Route::patch('/dashboard/kegiatan/{activity}/biaya', [SchoolFeatureController::class, 'updateActivityCost'])
        ->name('database.activity.cost.update');
    Route::post('/dashboard/kegiatan/{activity}/tagihan', [SchoolFeatureController::class, 'generateActivityBills'])
        ->name('database.activity.bills.generate');
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
    Route::patch('/dashboard/pembayaran/{payment}/batalkan', [SchoolFeatureController::class, 'cancelPayment'])
        ->name('database.payment.cancel');
    Route::get('/dashboard/pembayaran/{payment}/bukti', [SchoolFeatureController::class, 'paymentProof'])
        ->name('database.payment.proof');
    Route::post('/dashboard/tabungan', [SchoolFeatureController::class, 'storeSavings'])
        ->name('database.savings.store');
    Route::post('/dashboard/tagihan/spp', [SchoolFeatureController::class, 'generateMonthlyBills'])
        ->name('database.bills.generate');
    Route::post('/dashboard/tagihan/siswa', [SchoolFeatureController::class, 'createStudentBill'])
        ->name('database.bills.store');
    Route::patch('/dashboard/tagihan/{bill}', [SchoolFeatureController::class, 'updateStudentBill'])
        ->name('database.bills.update');
    Route::delete('/dashboard/tagihan/{bill}', [SchoolFeatureController::class, 'destroyStudentBill'])
        ->name('database.bills.destroy');
    Route::post('/dashboard/keluarga/tautkan', [SchoolFeatureController::class, 'linkParentToFamily'])
        ->name('database.family.link-parent');
    Route::post('/dashboard/keluarga/akun-orang-tua', [SchoolFeatureController::class, 'createAndLinkParent'])
        ->name('database.family.create-parent');
});
Route::middleware(['auth', EnsurePasswordChange::class, SetAuditUserContext::class])->group(function () {
    Route::get('/admin/password-reset-requests', [PasswordResetRequestsController::class, 'index'])
        ->name('admin.password-reset-requests.index');
    Route::post('/admin/password-reset-requests/{requestId}/approve', [PasswordResetRequestsController::class, 'approve'])
        ->name('admin.password-reset-requests.approve');
    Route::post('/admin/password-reset-requests/{requestId}/reject', [PasswordResetRequestsController::class, 'reject'])
        ->name('admin.password-reset-requests.reject');
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
