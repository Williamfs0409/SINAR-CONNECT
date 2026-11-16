@if ($dashboardRoleKey === 'teacher')
    <section class="database-action-panel">
        <div class="database-section-heading">
            <div>
                <h2>Unggah foto dokumentasi</h2>
                <p>Contoh tampilan unggah foto kegiatan untuk guru.</p>
            </div>
        </div>
        <div class="database-inline-form" aria-label="Simulasi unggah foto">
            <strong>Bermain &amp; belajar di kebun · 13 November 2026</strong>
            <select aria-label="Tahap kegiatan" disabled>
                <option>Selama kegiatan</option>
            </select>
            <input type="file" aria-label="Pilih foto dokumentasi" accept="image/jpeg,image/png,image/webp" disabled>
            <button class="database-button" type="button" disabled>Upload foto</button>
        </div>
        <p class="database-empty-note">Mode pratinjau: file tidak dapat dipilih atau disimpan. Masuk sebagai guru untuk mengunggah foto.</p>
    </section>
@else
    <section class="database-action-panel">
        <div class="database-section-heading">
            <div>
                <h2>Dokumentasi dari guru</h2>
                <p>Contoh foto kegiatan yang dapat dilihat orang tua.</p>
            </div>
        </div>
        <div class="database-photo-grid">
            <article class="database-photo-card">
                <img src="{{ asset('images/school-illustration.svg') }}" alt="Ilustrasi contoh kegiatan belajar di sekolah" loading="lazy">
                <strong>Bermain &amp; belajar di kebun</strong>
                <small>13 November 2026 · Contoh pratinjau</small>
            </article>
        </div>
        <p class="database-empty-note">Ilustrasi ini hanya contoh. Masuk ke akun orang tua untuk melihat foto dokumentasi asli.</p>
    </section>
@endif
