import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { Bidang, BidangPilih, BidangSakelar, BidangTeks, BidangTextarea } from '@/components/admin/bidang';
import { HalamanAdmin, KartuAdmin } from '@/components/admin/halaman-admin';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

type MediaRingkas = { id: number; url: string | null; judul: string } | null;
type P = Record<string, string | number | boolean | null> & { media: Record<string, MediaRingkas> };
type Info = {
    domain_kanonis: string;
    batas_server_mb: number;
    batas_php: { upload_max_filesize: string; post_max_size: string };
    modul_lengkap_aktif: { id: number; judul: string; nomor_versi: string } | null;
    mailer: string;
};

const s = (v: string | number | boolean | null | undefined | object) => (v === null || v === undefined || typeof v === 'object' ? '' : String(v));

function BidangGambar({
    id,
    label,
    media,
    onBerkas,
    hapus,
    onHapus,
    galat,
    petunjuk,
    accept = 'image/jpeg,image/png,image/webp',
}: {
    id: string;
    label: string;
    media: MediaRingkas;
    onBerkas: (f: File | null) => void;
    hapus: boolean;
    onHapus: (v: boolean) => void;
    galat?: string;
    petunjuk?: string;
    accept?: string;
}) {
    return (
        <Bidang id={id} label={label} galat={galat} petunjuk={petunjuk}>
            <div className="flex items-center gap-3">
                <div className="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-md border bg-muted">
                    {media?.url && !hapus ? (
                        <img src={media.url} alt="" className="size-full object-contain" />
                    ) : (
                        <span className="text-xs text-muted-foreground">Bawaan</span>
                    )}
                </div>
                <Input id={id} type="file" accept={accept} onChange={(e) => onBerkas(e.target.files?.[0] ?? null)} />
            </div>
            {media && (
                <label className="flex items-center gap-2 text-xs">
                    <Checkbox checked={hapus} onCheckedChange={(v) => onHapus(v === true)} /> Kembalikan ke bawaan
                </label>
            )}
        </Bidang>
    );
}

export default function Pengaturan({ pengaturan: p, info, tab }: { pengaturan: P; info: Info; tab: string }) {
    const identitas = useForm<Record<string, string | boolean | File | null>>({
        nama_aplikasi: s(p.nama_aplikasi),
        nama_singkat: s(p.nama_singkat),
        tagline: s(p.tagline),
        deskripsi_singkat: s(p.deskripsi_singkat),
        nama_pemilik: s(p.nama_pemilik),
        nama_institusi: s(p.nama_institusi),
        zona_waktu: s(p.zona_waktu),
        logo_terang: null,
        logo_gelap: null,
        favicon: null,
        gambar_sampul: null,
        hapus_logo_terang: false,
        hapus_logo_gelap: false,
        hapus_favicon: false,
        hapus_gambar_sampul: false,
    });
    const kontak = useForm({
        surel_kontak: s(p.surel_kontak),
        nomor_whatsapp: s(p.nomor_whatsapp),
        alamat_publik: s(p.alamat_publik),
        kota_kabupaten: s(p.kota_kabupaten),
        provinsi: s(p.provinsi),
    });
    const tampilan = useForm({
        warna_utama: s(p.warna_utama),
        warna_pendamping: s(p.warna_pendamping),
        tema_bawaan: s(p.tema_bawaan),
        teks_footer: s(p.teks_footer),
        tahun_hak_cipta_awal: s(p.tahun_hak_cipta_awal),
    });
    const seo = useForm<Record<string, string | boolean | File | null>>({
        judul_seo_bawaan: s(p.judul_seo_bawaan),
        templat_judul_seo: s(p.templat_judul_seo),
        deskripsi_seo_bawaan: s(p.deskripsi_seo_bawaan),
        jenis_identitas_penerbit: s(p.jenis_identitas_penerbit),
        kode_verifikasi_google: s(p.kode_verifikasi_google),
        kode_verifikasi_bing: s(p.kode_verifikasi_bing),
        gambar_og: null,
        hapus_gambar_og: false,
    });
    const dokumen = useForm({
        izinkan_unduh: Boolean(p.izinkan_unduh),
        batas_unggah_mb: s(p.batas_unggah_mb),
        panduan_penamaan_versi: s(p.panduan_penamaan_versi),
    });
    const privasi = useForm({
        statistik_aktif: Boolean(p.statistik_aktif),
        kecualikan_admin: Boolean(p.kecualikan_admin),
        kecualikan_bot: Boolean(p.kecualikan_bot),
        retensi_statistik_hari: s(p.retensi_statistik_hari),
        retensi_pesan_hari: s(p.retensi_pesan_hari),
    });
    const kirim = (nama: string, f: { post: (u: string, o?: object) => void }) => (e: React.FormEvent) => {
        e.preventDefault();
        f.post(`/admin/pengaturan/${nama}`, { preserveScroll: true, forceFormData: nama === 'identitas' || nama === 'seo' });
    };
    const ei = identitas.errors as Record<string, string>;
    const es = seo.errors as Record<string, string>;

    return (
        <HalamanAdmin
            judul="Pengaturan Website"
            jejak={[{ judul: 'Pengaturan' }]}
            deskripsi={`Domain kanonis berasal dari konfigurasi server (APP_URL): ${info.domain_kanonis}. Kredensial DB/SMTP/APP_KEY dikelola di environment dan tidak ditampilkan.`}
        >
            <Tabs defaultValue={tab}>
                <TabsList>
                    {['identitas', 'kontak', 'tampilan', 'seo', 'dokumen', 'privasi'].map((t) => (
                        <TabsTrigger key={t} value={t} className="capitalize">
                            {t === 'seo' ? 'SEO' : t === 'privasi' ? 'Privasi & Statistik' : t === 'kontak' ? 'Kontak Publik' : t}
                        </TabsTrigger>
                    ))}
                </TabsList>
                <TabsContent value="identitas">
                    <KartuAdmin>
                        <form className="grid gap-4 md:grid-cols-2" onSubmit={kirim('identitas', identitas)}>
                            <BidangTeks
                                id="nama_aplikasi"
                                label="Nama aplikasi"
                                wajib
                                nilai={s(identitas.data.nama_aplikasi)}
                                onUbah={(v) => identitas.setData('nama_aplikasi', v)}
                                galat={ei.nama_aplikasi}
                            />
                            <BidangTeks
                                id="nama_singkat"
                                label="Nama singkat"
                                wajib
                                nilai={s(identitas.data.nama_singkat)}
                                onUbah={(v) => identitas.setData('nama_singkat', v)}
                                galat={ei.nama_singkat}
                            />
                            <BidangTeks
                                id="tagline"
                                label="Tagline"
                                nilai={s(identitas.data.tagline)}
                                onUbah={(v) => identitas.setData('tagline', v)}
                                galat={ei.tagline}
                            />
                            <BidangPilih
                                id="zona_waktu"
                                label="Zona waktu penyajian"
                                nilai={s(identitas.data.zona_waktu)}
                                onUbah={(v) => identitas.setData('zona_waktu', v)}
                                opsi={[
                                    { nilai: 'Asia/Jakarta', label: 'Asia/Jakarta (WIB)' },
                                    { nilai: 'Asia/Makassar', label: 'Asia/Makassar (WITA)' },
                                    { nilai: 'Asia/Jayapura', label: 'Asia/Jayapura (WIT)' },
                                ]}
                            />
                            <BidangTextarea
                                id="deskripsi_singkat"
                                label="Deskripsi singkat"
                                className="md:col-span-2"
                                nilai={s(identitas.data.deskripsi_singkat)}
                                onUbah={(v) => identitas.setData('deskripsi_singkat', v)}
                                galat={ei.deskripsi_singkat}
                            />
                            <BidangTeks
                                id="nama_pemilik"
                                label="Nama pemilik/penyusun"
                                nilai={s(identitas.data.nama_pemilik)}
                                onUbah={(v) => identitas.setData('nama_pemilik', v)}
                            />
                            <BidangTeks
                                id="nama_institusi"
                                label="Organisasi (opsional)"
                                nilai={s(identitas.data.nama_institusi)}
                                onUbah={(v) => identitas.setData('nama_institusi', v)}
                            />
                            <BidangGambar
                                id="logo_terang"
                                label="Logo (tema terang)"
                                media={p.media.logo_terang}
                                onBerkas={(f) => identitas.setData('logo_terang', f)}
                                hapus={Boolean(identitas.data.hapus_logo_terang)}
                                onHapus={(v) => identitas.setData('hapus_logo_terang', v)}
                                galat={ei.logo_terang}
                            />
                            <BidangGambar
                                id="logo_gelap"
                                label="Logo (tema gelap)"
                                media={p.media.logo_gelap}
                                onBerkas={(f) => identitas.setData('logo_gelap', f)}
                                hapus={Boolean(identitas.data.hapus_logo_gelap)}
                                onHapus={(v) => identitas.setData('hapus_logo_gelap', v)}
                                galat={ei.logo_gelap}
                            />
                            <BidangGambar
                                id="favicon"
                                label="Favicon (PNG persegi)"
                                accept="image/png"
                                media={p.media.favicon}
                                onBerkas={(f) => identitas.setData('favicon', f)}
                                hapus={Boolean(identitas.data.hapus_favicon)}
                                onHapus={(v) => identitas.setData('hapus_favicon', v)}
                                galat={ei.favicon}
                                petunjuk="PNG ≤ 1 MB, disarankan 512×512."
                            />
                            <BidangGambar
                                id="gambar_sampul"
                                label="Sampul bawaan"
                                media={p.media.gambar_sampul}
                                onBerkas={(f) => identitas.setData('gambar_sampul', f)}
                                hapus={Boolean(identitas.data.hapus_gambar_sampul)}
                                onHapus={(v) => identitas.setData('hapus_gambar_sampul', v)}
                                galat={ei.gambar_sampul}
                                petunjuk="Gambar pratinjau saat tautan website dibagikan (WhatsApp, Facebook, dll.); tidak tampil di halaman. Dipakai bila halaman tidak punya gambar sendiri dan “Gambar Open Graph bawaan” (tab SEO) kosong. Disarankan 1200×630 piksel."
                            />
                            <p className="text-xs text-muted-foreground md:col-span-2">
                                Bahasa antarmuka: Indonesia. Perubahan nama/logo berlaku di header, footer, login, dashboard, judul browser, metadata, dan surel
                                aplikasi.
                            </p>
                            <Button type="submit" className="w-fit" disabled={identitas.processing}>
                                <Save /> Simpan identitas
                            </Button>
                        </form>
                    </KartuAdmin>
                </TabsContent>
                <TabsContent value="kontak">
                    <KartuAdmin>
                        <form className="grid gap-4 md:grid-cols-2" onSubmit={kirim('kontak', kontak)}>
                            <BidangTeks
                                id="surel_kontak"
                                label="Surel publik"
                                tipe="email"
                                nilai={kontak.data.surel_kontak}
                                onUbah={(v) => kontak.setData('surel_kontak', v)}
                                galat={kontak.errors.surel_kontak}
                            />
                            <BidangTeks
                                id="nomor_whatsapp"
                                label="WhatsApp publik"
                                nilai={kontak.data.nomor_whatsapp}
                                onUbah={(v) => kontak.setData('nomor_whatsapp', v)}
                                galat={kontak.errors.nomor_whatsapp}
                            />
                            <BidangTeks
                                id="alamat_publik"
                                label="Alamat korespondensi (yang dipilih untuk publik)"
                                className="md:col-span-2"
                                nilai={kontak.data.alamat_publik}
                                onUbah={(v) => kontak.setData('alamat_publik', v)}
                            />
                            <BidangTeks
                                id="kota"
                                label="Kota/Kabupaten"
                                nilai={kontak.data.kota_kabupaten}
                                onUbah={(v) => kontak.setData('kota_kabupaten', v)}
                            />
                            <BidangTeks id="provinsi" label="Provinsi" nilai={kontak.data.provinsi} onUbah={(v) => kontak.setData('provinsi', v)} />
                            <p className="text-xs text-muted-foreground md:col-span-2">Tautan sosial dikelola di menu Menu & Tautan Sosial.</p>
                            <Button type="submit" className="w-fit" disabled={kontak.processing}>
                                <Save /> Simpan kontak
                            </Button>
                        </form>
                    </KartuAdmin>
                </TabsContent>
                <TabsContent value="tampilan">
                    <KartuAdmin>
                        <form className="grid gap-4 md:grid-cols-2" onSubmit={kirim('tampilan', tampilan)}>
                            <Bidang
                                id="warna_utama"
                                label="Warna utama"
                                galat={tampilan.errors.warna_utama}
                                petunjuk="Heksadesimal, mis. #BE185D. Pastikan kontras teks putih memadai."
                            >
                                <div className="flex gap-2">
                                    <input
                                        type="color"
                                        aria-label="Pilih warna utama"
                                        value={tampilan.data.warna_utama}
                                        onChange={(e) => tampilan.setData('warna_utama', e.target.value.toUpperCase())}
                                        className="h-9 w-12 rounded border"
                                    />
                                    <Input
                                        id="warna_utama"
                                        value={tampilan.data.warna_utama}
                                        onChange={(e) => tampilan.setData('warna_utama', e.target.value)}
                                    />
                                </div>
                            </Bidang>
                            <Bidang id="warna_pendamping" label="Warna pendamping" galat={tampilan.errors.warna_pendamping}>
                                <div className="flex gap-2">
                                    <input
                                        type="color"
                                        aria-label="Pilih warna pendamping"
                                        value={tampilan.data.warna_pendamping}
                                        onChange={(e) => tampilan.setData('warna_pendamping', e.target.value.toUpperCase())}
                                        className="h-9 w-12 rounded border"
                                    />
                                    <Input
                                        id="warna_pendamping"
                                        value={tampilan.data.warna_pendamping}
                                        onChange={(e) => tampilan.setData('warna_pendamping', e.target.value)}
                                    />
                                </div>
                            </Bidang>
                            <BidangPilih
                                id="tema_bawaan"
                                label="Tema bawaan pengunjung"
                                nilai={tampilan.data.tema_bawaan}
                                onUbah={(v) => tampilan.setData('tema_bawaan', v)}
                                opsi={[
                                    { nilai: 'sistem', label: 'Ikuti sistem' },
                                    { nilai: 'terang', label: 'Terang' },
                                    { nilai: 'gelap', label: 'Gelap' },
                                ]}
                            />
                            <BidangTeks
                                id="tahun"
                                label="Tahun hak cipta awal"
                                tipe="number"
                                nilai={tampilan.data.tahun_hak_cipta_awal}
                                onUbah={(v) => tampilan.setData('tahun_hak_cipta_awal', v)}
                                galat={tampilan.errors.tahun_hak_cipta_awal}
                            />
                            <BidangTextarea
                                id="teks_footer"
                                label="Teks footer"
                                className="md:col-span-2"
                                baris={2}
                                nilai={tampilan.data.teks_footer}
                                onUbah={(v) => tampilan.setData('teks_footer', v)}
                            />
                            <p className="text-xs text-muted-foreground md:col-span-2">Judul, CTA, dan urutan bagian beranda diatur di menu Beranda Dinamis.</p>
                            <Button type="submit" className="w-fit" disabled={tampilan.processing}>
                                <Save /> Simpan tampilan
                            </Button>
                        </form>
                    </KartuAdmin>
                </TabsContent>
                <TabsContent value="seo">
                    <KartuAdmin>
                        <form className="grid gap-4 md:grid-cols-2" onSubmit={kirim('seo', seo)}>
                            <BidangTeks
                                id="judul_seo_bawaan"
                                label="Judul bawaan (beranda)"
                                className="md:col-span-2"
                                nilai={s(seo.data.judul_seo_bawaan)}
                                onUbah={(v) => seo.setData('judul_seo_bawaan', v)}
                                galat={es.judul_seo_bawaan}
                            />
                            <BidangTeks
                                id="templat"
                                label="Templat judul halaman"
                                wajib
                                nilai={s(seo.data.templat_judul_seo)}
                                onUbah={(v) => seo.setData('templat_judul_seo', v)}
                                galat={es.templat_judul_seo}
                                petunjuk="Gunakan %judul% dan %nama%."
                            />
                            <BidangPilih
                                id="penerbit"
                                label="Identitas penerbit (JSON-LD)"
                                nilai={s(seo.data.jenis_identitas_penerbit)}
                                onUbah={(v) => seo.setData('jenis_identitas_penerbit', v)}
                                opsi={[
                                    { nilai: 'orang', label: 'Person (pemilik)' },
                                    { nilai: 'organisasi', label: 'Organization (organisasi)' },
                                ]}
                            />
                            <BidangTextarea
                                id="deskripsi_seo"
                                label="Deskripsi bawaan"
                                className="md:col-span-2"
                                baris={2}
                                maks={300}
                                nilai={s(seo.data.deskripsi_seo_bawaan)}
                                onUbah={(v) => seo.setData('deskripsi_seo_bawaan', v)}
                                galat={es.deskripsi_seo_bawaan}
                            />
                            <BidangTeks
                                id="google"
                                label="Kode verifikasi Google"
                                nilai={s(seo.data.kode_verifikasi_google)}
                                onUbah={(v) => seo.setData('kode_verifikasi_google', v)}
                                galat={es.kode_verifikasi_google}
                            />
                            <BidangTeks
                                id="bing"
                                label="Kode verifikasi Bing"
                                nilai={s(seo.data.kode_verifikasi_bing)}
                                onUbah={(v) => seo.setData('kode_verifikasi_bing', v)}
                                galat={es.kode_verifikasi_bing}
                            />
                            <BidangGambar
                                id="gambar_og"
                                label="Gambar Open Graph bawaan"
                                media={p.media.gambar_og}
                                onBerkas={(f) => seo.setData('gambar_og', f)}
                                hapus={Boolean(seo.data.hapus_gambar_og)}
                                onHapus={(v) => seo.setData('hapus_gambar_og', v)}
                                galat={es.gambar_og}
                                petunjuk="Gambar pratinjau saat tautan dibagikan (WhatsApp, Facebook, dll.). Diutamakan daripada “Sampul bawaan” (tab Identitas); halaman yang punya gambar sendiri tetap memakai gambarnya. Disarankan 1200×630 piksel."
                            />
                            <Button type="submit" className="w-fit self-end" disabled={seo.processing}>
                                <Save /> Simpan SEO
                            </Button>
                        </form>
                    </KartuAdmin>
                </TabsContent>
                <TabsContent value="dokumen">
                    <KartuAdmin>
                        <form className="grid gap-4 md:grid-cols-2" onSubmit={kirim('dokumen', dokumen)}>
                            <BidangSakelar
                                id="izinkan_unduh"
                                label="Izin unduh global"
                                petunjuk="Bila nonaktif, semua unduhan publik ditutup (endpoint menolak). Bukan DRM."
                                nilai={dokumen.data.izinkan_unduh}
                                onUbah={(v) => dokumen.setData('izinkan_unduh', v)}
                            />
                            <BidangTeks
                                id="batas"
                                label="Batas unggah (MB)"
                                tipe="number"
                                nilai={dokumen.data.batas_unggah_mb}
                                onUbah={(v) => dokumen.setData('batas_unggah_mb', v)}
                                galat={dokumen.errors.batas_unggah_mb}
                                petunjuk={`Maksimum server ${info.batas_server_mb} MB (PHP: upload_max_filesize=${info.batas_php.upload_max_filesize}, post_max_size=${info.batas_php.post_max_size}).`}
                            />
                            <BidangTextarea
                                id="panduan"
                                label="Panduan penamaan versi"
                                className="md:col-span-2"
                                nilai={dokumen.data.panduan_penamaan_versi}
                                onUbah={(v) => dokumen.setData('panduan_penamaan_versi', v)}
                            />
                            <p className="text-sm md:col-span-2">
                                Dokumen lengkap aktif:{' '}
                                {info.modul_lengkap_aktif ? `${info.modul_lengkap_aktif.judul} (v${info.modul_lengkap_aktif.nomor_versi})` : 'belum ada'} —
                                ganti melalui menu Dokumen PDF.
                            </p>
                            <Button type="submit" className="w-fit" disabled={dokumen.processing}>
                                <Save /> Simpan pengaturan dokumen
                            </Button>
                        </form>
                    </KartuAdmin>
                </TabsContent>
                <TabsContent value="privasi">
                    <KartuAdmin>
                        <form className="grid gap-4 md:grid-cols-2" onSubmit={kirim('privasi', privasi)}>
                            <BidangSakelar
                                id="statistik_aktif"
                                label="Pencatatan statistik"
                                nilai={privasi.data.statistik_aktif}
                                onUbah={(v) => privasi.setData('statistik_aktif', v)}
                            />
                            <BidangSakelar
                                id="kecualikan_admin"
                                label="Kecualikan admin yang masuk"
                                nilai={privasi.data.kecualikan_admin}
                                onUbah={(v) => privasi.setData('kecualikan_admin', v)}
                            />
                            <BidangSakelar
                                id="kecualikan_bot"
                                label="Kecualikan bot/crawler"
                                nilai={privasi.data.kecualikan_bot}
                                onUbah={(v) => privasi.setData('kecualikan_bot', v)}
                            />
                            <span />
                            <BidangTeks
                                id="retensi_stat"
                                label="Retensi statistik (hari)"
                                tipe="number"
                                nilai={privasi.data.retensi_statistik_hari}
                                onUbah={(v) => privasi.setData('retensi_statistik_hari', v)}
                                galat={privasi.errors.retensi_statistik_hari}
                            />
                            <BidangTeks
                                id="retensi_pesan"
                                label="Retensi pesan arsip (hari)"
                                tipe="number"
                                nilai={privasi.data.retensi_pesan_hari}
                                onUbah={(v) => privasi.setData('retensi_pesan_hari', v)}
                                galat={privasi.errors.retensi_pesan_hari}
                            />
                            <div className="rounded-lg bg-muted/50 p-4 text-sm leading-relaxed md:col-span-2">
                                <p className="font-semibold">Cara penghitungan</p>
                                <ul className="mt-2 list-disc space-y-1 pl-5">
                                    <li>
                                        Kunjungan: satu per sidik pengunjung, per jalur halaman, per tanggal lokal (hanya respons 200, bukan partial
                                        reload/prefetch).
                                    </li>
                                    <li>
                                        Sidik: HMAC dari IP terpotong + user agent dengan kunci aplikasi; IP mentah tidak disimpan. Pengunjung unik adalah
                                        perkiraan.
                                    </li>
                                    <li>Baca/unduh PDF: satu per sidik, per versi, per jenis, per hari; permintaan Range lanjutan tidak dihitung.</li>
                                    <li>
                                        Data lebih tua dari masa retensi dihapus oleh perintah terjadwal <code>ping:bersihkan-retensi</code>.
                                    </li>
                                </ul>
                            </div>
                            <Button type="submit" className="w-fit" disabled={privasi.processing}>
                                <Save /> Simpan privasi
                            </Button>
                        </form>
                    </KartuAdmin>
                </TabsContent>
            </Tabs>
        </HalamanAdmin>
    );
}
