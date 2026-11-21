<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrationDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_can_upload_missing_and_replace_rejected_registration_documents(): void
    {
        Storage::fake('local');
        $this->createRegistrationTables();
        $parent = User::factory()->create(['role' => 'parent']);
        $this->createApplicationFor($parent, 1);
        $oldPath = 'pendaftaran/ditolak.pdf';
        Storage::disk('local')->put($oldPath, '%PDF-1.4 old document');
        DB::table('dokumen_pendaftaran')->insert([
            'id_pendaftaran' => 1,
            'jenis' => 'Akta Kelahiran',
            'file_path' => $oldPath,
            'status_verifikasi' => 'Tidak Valid',
            'created_at' => now(),
        ]);

        $this->actingAs($parent)
            ->post(route('database.registration.documents.upload', ['registration' => 1]), [
                'documents' => [
                    'akta_kelahiran' => UploadedFile::fake()->createWithContent('akta.pdf', "%PDF-1.4\nakta"),
                    'formulir_pendaftaran' => UploadedFile::fake()->createWithContent('formulir.pdf', "%PDF-1.4\nformulir"),
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('dokumen_pendaftaran', [
            'id_pendaftaran' => 1,
            'jenis' => 'Akta Kelahiran',
            'status_verifikasi' => 'Menunggu',
        ]);
        $this->assertDatabaseHas('dokumen_pendaftaran', [
            'id_pendaftaran' => 1,
            'jenis' => 'Formulir Pendaftaran',
            'status_verifikasi' => 'Menunggu',
        ]);
        Storage::disk('local')->assertMissing($oldPath);
        foreach (DB::table('dokumen_pendaftaran')->pluck('file_path') as $path) {
            Storage::disk('local')->assertExists($path);
        }
    }

    public function test_admin_cannot_verify_or_accept_an_application_with_missing_required_documents(): void
    {
        $this->createRegistrationTables();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $parent = User::factory()->create(['role' => 'parent']);
        $this->createApplicationFor($parent, 1);

        $this->actingAs($admin)
            ->post(route('database.registration.review', ['registration' => 1]), [
                'decision' => 'verify',
            ])
            ->assertSessionHasErrors('decision');
        $this->assertDatabaseHas('pendaftaran', ['id_pendaftaran' => 1, 'status' => 'Diajukan']);

        DB::table('pendaftaran')->where('id_pendaftaran', 1)->update(['status' => 'Diverifikasi']);
        $this->post(route('database.registration.review', ['registration' => 1]), [
            'decision' => 'accept',
        ])->assertSessionHasErrors('decision');
        $this->assertDatabaseHas('pendaftaran', ['id_pendaftaran' => 1, 'status' => 'Diverifikasi']);
    }

    public function test_parent_can_view_their_registration_document(): void
    {
        Storage::fake('local');
        $this->createRegistrationTables();
        $parent = User::factory()->create(['role' => 'parent']);
        $this->createApplicationFor($parent, 1);
        $path = 'pendaftaran/akta.pdf';
        $contents = "%PDF-1.4\nakta";
        Storage::disk('local')->put($path, $contents);
        DB::table('dokumen_pendaftaran')->insert([
            'id_pendaftaran' => 1,
            'jenis' => 'Akta Kelahiran',
            'file_path' => $path,
            'status_verifikasi' => 'Menunggu',
            'created_at' => now(),
        ]);

        $this->actingAs($parent)
            ->get(route('database.registration.document.file', ['document' => 1]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertStreamedContent($contents);
    }

    public function test_admin_can_save_document_statuses_with_registration_decision(): void
    {
        $this->createRegistrationTables();
        Schema::table('pendaftaran', function (Blueprint $table): void {
            $table->string('catatan_verifikasi')->nullable();
            $table->unsignedBigInteger('id_user_verifikator')->nullable();
            $table->timestamp('tanggal_verifikasi')->nullable();
        });

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $parent = User::factory()->create(['role' => 'parent']);
        $this->createApplicationFor($parent, 1);

        $documents = [];
        foreach (config('registration.required_documents') as $index => $jenis) {
            $id = DB::table('dokumen_pendaftaran')->insertGetId([
                'id_pendaftaran' => 1,
                'jenis' => $jenis,
                'file_path' => "pendaftaran/doc-{$index}.pdf",
                'status_verifikasi' => 'Menunggu',
                'created_at' => now(),
            ]);
            $documents[$id] = [
                'status_verifikasi' => 'Valid',
                'catatan' => $index === 0 ? 'Jelas' : null,
            ];
        }

        $this->actingAs($admin)
            ->post(route('database.registration.review', ['registration' => 1]), [
                'decision' => 'verify',
                'note' => 'Lengkap',
                'documents' => $documents,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pendaftaran', [
            'id_pendaftaran' => 1,
            'status' => 'Diverifikasi',
            'catatan_verifikasi' => 'Lengkap',
        ]);
        $this->assertDatabaseHas('dokumen_pendaftaran', [
            'id_pendaftaran' => 1,
            'jenis' => config('registration.required_documents')[0],
            'status_verifikasi' => 'Valid',
            'catatan' => 'Jelas',
        ]);
    }

    private function createRegistrationTables(): void
    {
        Schema::create('keluarga', function (Blueprint $table): void {
            $table->increments('id_keluarga');
            $table->unsignedBigInteger('id_user')->nullable();
        });
        Schema::create('siswa', function (Blueprint $table): void {
            $table->increments('id_siswa');
            $table->unsignedInteger('id_keluarga');
            $table->string('nama_lengkap');
        });
        Schema::create('pendaftaran', function (Blueprint $table): void {
            $table->increments('id_pendaftaran');
            $table->unsignedInteger('id_siswa');
            $table->string('status');
        });
        Schema::create('dokumen_pendaftaran', function (Blueprint $table): void {
            $table->increments('id_dokumen');
            $table->unsignedInteger('id_pendaftaran');
            $table->string('jenis');
            $table->string('file_path');
            $table->string('status_verifikasi');
            $table->string('catatan')->nullable();
            $table->timestamps();
            $table->unique(['id_pendaftaran', 'jenis']);
        });
    }

    private function createApplicationFor(User $parent, int $registrationId): void
    {
        DB::table('keluarga')->insert([
            'id_keluarga' => $registrationId,
            'id_user' => $parent->getKey(),
        ]);
        DB::table('siswa')->insert([
            'id_siswa' => $registrationId,
            'id_keluarga' => $registrationId,
            'nama_lengkap' => 'Siswa Uji',
        ]);
        DB::table('pendaftaran')->insert([
            'id_pendaftaran' => $registrationId,
            'id_siswa' => $registrationId,
            'status' => 'Diajukan',
        ]);
    }
}
