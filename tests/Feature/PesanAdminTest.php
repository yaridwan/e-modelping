<?php

namespace Tests\Feature;

use App\Models\PesanKontak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/** Aksi pengelolaan pesan masuk: status cepat, aksi massal, hapus, filter kotak masuk/arsip, detail. */
class PesanAdminTest extends TestCase
{
    use RefreshDatabase;

    private function pesan(array $atribut = []): PesanKontak
    {
        static $n = 0;
        $n++;

        return PesanKontak::query()->forceCreate([
            'nama_pengirim' => "Pengirim {$n}",
            'surel_pengirim' => "p{$n}@contoh.test",
            'subjek' => "Subjek {$n}",
            'isi_pesan' => "Isi pesan rahasia {$n}",
            'persetujuan_privasi' => true,
            'status' => 'baru',
            'data_contoh' => false,
            ...$atribut,
        ]);
    }

    public function test_status_cepat_mengatur_stempel_waktu_dan_tidak_menghapus_catatan(): void
    {
        $this->seedDasar();
        $admin = $this->admin();
        $p = $this->pesan(['catatan_admin' => 'Catatan penting']);

        $this->actingAs($admin)->patch("/admin/pesan/{$p->id}/status", ['status' => 'ditindaklanjuti'])->assertSessionHasNoErrors()->assertSessionHas('sukses');
        $p->refresh();
        $this->assertSame('ditindaklanjuti', $p->status->value);
        $this->assertNotNull($p->dibaca_pada);
        $this->assertNotNull($p->ditindaklanjuti_pada);
        $this->assertSame('Catatan penting', $p->catatan_admin);

        // Tandai belum dibaca: dibaca_pada dikosongkan; dari halaman detail kembali ke daftar.
        $this->actingAs($admin)->from("/admin/pesan/{$p->id}")->patch("/admin/pesan/{$p->id}/status", ['status' => 'baru'])->assertRedirect(route('admin.pesan.index'));
        $p->refresh();
        $this->assertSame('baru', $p->status->value);
        $this->assertNull($p->dibaca_pada);
        $this->assertSame('Catatan penting', $p->catatan_admin);

        $this->actingAs($admin)->patch("/admin/pesan/{$p->id}/status", ['status' => 'hapus'])->assertSessionHasErrors('status');
    }

    public function test_aksi_massal_status_dan_hapus(): void
    {
        $this->seedDasar();
        $admin = $this->admin();
        [$a, $b, $c] = [$this->pesan(), $this->pesan(), $this->pesan()];

        $this->actingAs($admin)->post('/admin/pesan/massal', ['id' => [$a->id, $b->id], 'aksi' => 'arsip'])->assertSessionHasNoErrors()->assertSessionHas('sukses', '2 pesan ditandai arsip.');
        $this->assertSame(['arsip', 'arsip', 'baru'], [$a->fresh()->status->value, $b->fresh()->status->value, $c->fresh()->status->value]);
        $this->assertNotNull($a->fresh()->dibaca_pada);

        $this->actingAs($admin)->post('/admin/pesan/massal', ['id' => [$a->id, $c->id], 'aksi' => 'hapus'])->assertSessionHas('sukses', '2 pesan dihapus permanen.');
        $this->assertNull($a->fresh());
        $this->assertNull($c->fresh());
        $this->assertNotNull($b->fresh());

        // Validasi: kosong, aksi tak dikenal, lebih dari 100.
        $this->actingAs($admin)->post('/admin/pesan/massal', ['id' => [], 'aksi' => 'arsip'])->assertSessionHasErrors('id');
        $this->actingAs($admin)->post('/admin/pesan/massal', ['id' => [$b->id], 'aksi' => 'kirim'])->assertSessionHasErrors('aksi');
        $this->actingAs($admin)->post('/admin/pesan/massal', ['id' => range(1, 101), 'aksi' => 'arsip'])->assertSessionHasErrors('id');

        // Log mencatat aksi tanpa isi pesan.
        $log = DB::table('log_aktivitas')->where('aksi', 'pesan_massal')->get();
        $this->assertCount(2, $log);
        $this->assertStringNotContainsString('Isi pesan rahasia', $log->pluck('perubahan')->implode(' ').$log->pluck('ringkasan')->implode(' '));
    }

    public function test_hapus_satu_pesan_permanen_dan_tercatat(): void
    {
        $this->seedDasar();
        $admin = $this->admin();
        $p = $this->pesan();

        $this->actingAs($admin)->from("/admin/pesan/{$p->id}")->delete("/admin/pesan/{$p->id}")->assertRedirect(route('admin.pesan.index'))->assertSessionHas('sukses');
        $this->assertNull($p->fresh());
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'hapus_pesan', 'entitas_id' => $p->id]);
        $this->actingAs($admin)->get("/admin/pesan/{$p->id}")->assertNotFound();
    }

    public function test_kotak_masuk_menyembunyikan_arsip_dan_tab_semua_menampilkannya(): void
    {
        $this->seedDasar();
        $admin = $this->admin();
        $this->pesan(['subjek' => 'Aktif']);
        $this->pesan(['subjek' => 'Diarsipkan', 'status' => 'arsip']);

        $this->actingAs($admin)->get('/admin/pesan')->assertInertia(fn (Assert $page) => $page->component('admin/pesan/index')
            ->has('pesan.data', 1)->where('pesan.data.0.subjek', 'Aktif')->where('filter.status', null)
            ->where('jumlah.arsip', 1)->where('jumlah.baru', 1));
        $this->actingAs($admin)->get('/admin/pesan?status=arsip')->assertInertia(fn (Assert $page) => $page->has('pesan.data', 1)->where('pesan.data.0.subjek', 'Diarsipkan'));
        $this->actingAs($admin)->get('/admin/pesan?status=semua')->assertInertia(fn (Assert $page) => $page->has('pesan.data', 2));
        $this->actingAs($admin)->get('/admin/pesan?status=palsu')->assertInertia(fn (Assert $page) => $page->where('filter.status', null)->has('pesan.data', 1));
    }

    public function test_detail_berisi_navigasi_pesan_lain_dan_riwayat_serta_lencana_menu(): void
    {
        $this->seedDasar();
        $admin = $this->admin();
        $lama = $this->pesan(['surel_pengirim' => 'sama@contoh.test', 'dibuat_pada' => now()->subDays(2)]);
        $tengah = $this->pesan(['surel_pengirim' => 'sama@contoh.test', 'dibuat_pada' => now()->subDay()]);
        $baru = $this->pesan(['dibuat_pada' => now()]);

        $this->actingAs($admin)->get("/admin/pesan/{$tengah->id}")->assertInertia(fn (Assert $page) => $page->component('admin/pesan/show')
            ->where('navigasi.sebelumnya', $baru->id)
            ->where('navigasi.berikutnya', $lama->id)
            ->where('pesan_lain.total', 1)
            ->where('pesan_lain.data.0.id', $lama->id)
            ->where('riwayat.0.ringkasan', 'Pesan ditandai dibaca')
            // Lencana menu: dua pesan lain masih "baru".
            ->where('lencana_admin.pesan_baru', 2));
    }
}
