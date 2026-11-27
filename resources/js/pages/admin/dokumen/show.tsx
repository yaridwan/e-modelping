import { Link, useForm } from '@inertiajs/react';
import { ExternalLink, Save } from 'lucide-react';
import { lazy, Suspense, useState } from 'react';
import { BidangSakelar, BidangTeks, BidangTextarea } from '@/components/admin/bidang';
import { LencanaStatus } from '@/components/admin/daftar';
import { HalamanAdmin, KartuAdmin } from '@/components/admin/halaman-admin';
import { TombolKonfirmasi } from '@/components/admin/konfirmasi';
import { Button } from '@/components/ui/button';
import { formatTanggal, formatUkuran } from '@/lib/utils';
import type { VersiRingkas } from './index';

const PenampilPdf = lazy(() => import('@/components/publik/penampil-pdf'));

type Versi = VersiRingkas & {
    catatan_perubahan: string | null;
    nama_asli: string | null;
    checksum: string | null;
    diterbitkan_oleh: string | null;
    url_pratinjau: string;
    url_publik: string | null;
    izin_unduh_efektif: boolean;
};

export default function DetailDokumen({ versi, riwayat }: { versi: Versi; riwayat: VersiRingkas[] }) {
    const [pratinjau, setPratinjau] = useState(false);
    const form = useForm({ judul_dokumen: versi.judul, catatan_perubahan: versi.catatan_perubahan ?? '', izinkan_unduh: versi.izinkan_unduh });

    return (
        <HalamanAdmin
            judul={`${versi.lingkup} · v${versi.nomor_versi}`}
            jejak={[{ judul: 'Dokumen PDF', href: '/admin/dokumen' }, { judul: `v${versi.nomor_versi}` }]}
            deskripsi={
                <>
                    <LencanaStatus status={versi.status} /> {versi.aktif && <strong className="ml-1 text-primary">Versi aktif</strong>}
                </>
            }
            aksi={
                <>
                    {!versi.aktif && (
                        <TombolKonfirmasi
                            url={`/admin/dokumen/${versi.id}/aktifkan`}
                            metode="post"
                            varian="default"
                            labelAksi="Terbitkan"
                            judul="Terbitkan versi ini?"
                            deskripsi="Versi ini menjadi versi aktif publik. Versi aktif sebelumnya pada lingkup yang sama akan diarsipkan."
                        >
                            Terbitkan & aktifkan
                        </TombolKonfirmasi>
                    )}
                    {versi.status !== 'arsip' && (
                        <TombolKonfirmasi
                            url={`/admin/dokumen/${versi.id}/arsipkan`}
                            metode="post"
                            labelAksi="Arsipkan"
                            judul="Arsipkan versi?"
                            deskripsi="Versi tidak lagi tersedia melalui endpoint publik. Berkas tetap tersimpan untuk admin."
                        >
                            Arsipkan
                        </TombolKonfirmasi>
                    )}
                    {versi.status === 'draf' && !versi.diterbitkan_pada && (
                        <TombolKonfirmasi
                            url={`/admin/dokumen/${versi.id}`}
                            judul="Hapus draf?"
                            deskripsi="Draf dan berkasnya (bila tidak dipakai di tempat lain) dihapus permanen."
                        >
                            Hapus draf
                        </TombolKonfirmasi>
                    )}
                </>
            }
        >
            <div className="grid gap-6 xl:grid-cols-[1fr_20rem]">
                <div className="space-y-6">
                    <KartuAdmin
                        judul="Pratinjau admin"
                        deskripsi="Endpoint privat (no-store, noindex) — dapat dibuka apa pun statusnya."
                        aksi={
                            <div className="flex gap-2">
                                <Button size="sm" onClick={() => setPratinjau((p) => !p)}>
                                    {pratinjau ? 'Tutup' : 'Tampilkan'}
                                </Button>
                                <Button asChild size="sm" variant="outline">
                                    <a href={versi.url_pratinjau} target="_blank" rel="noopener">
                                        <ExternalLink /> Tab baru
                                    </a>
                                </Button>
                            </div>
                        }
                    >
                        {pratinjau ? (
                            <Suspense fallback={<p className="text-sm text-muted-foreground">Menyiapkan penampil…</p>}>
                                <PenampilPdf url={versi.url_pratinjau} judul={versi.judul} />
                            </Suspense>
                        ) : (
                            <p className="text-sm text-muted-foreground">Klik “Tampilkan” untuk meninjau isi PDF sebelum menerbitkan.</p>
                        )}
                    </KartuAdmin>
                    <KartuAdmin judul="Metadata">
                        <form
                            className="grid gap-4"
                            onSubmit={(e) => {
                                e.preventDefault();
                                form.patch(`/admin/dokumen/${versi.id}`, { preserveScroll: true });
                            }}
                        >
                            <BidangTeks
                                id="judul_dokumen"
                                label="Judul dokumen"
                                wajib
                                nilai={form.data.judul_dokumen}
                                onUbah={(v) => form.setData('judul_dokumen', v)}
                                galat={form.errors.judul_dokumen}
                            />
                            <BidangTextarea
                                id="catatan_perubahan"
                                label="Catatan perubahan"
                                nilai={form.data.catatan_perubahan}
                                onUbah={(v) => form.setData('catatan_perubahan', v)}
                                galat={form.errors.catatan_perubahan}
                            />
                            <BidangSakelar
                                id="izinkan_unduh"
                                label="Izinkan unduh"
                                petunjuk={
                                    versi.izin_unduh_efektif
                                        ? 'Unduhan publik saat ini aktif.'
                                        : 'Unduhan publik saat ini tertutup (dokumen atau pengaturan global).'
                                }
                                nilai={form.data.izinkan_unduh}
                                onUbah={(v) => form.setData('izinkan_unduh', v)}
                            />
                            <Button type="submit" className="w-fit" disabled={form.processing}>
                                <Save /> Simpan metadata
                            </Button>
                        </form>
                    </KartuAdmin>
                </div>
                <div className="space-y-6">
                    <KartuAdmin judul="Informasi berkas">
                        <dl className="space-y-2 text-sm">
                            {[
                                ['Nama asli', versi.nama_asli],
                                ['Ukuran', formatUkuran(versi.ukuran_bita)],
                                ['Halaman', versi.jumlah_halaman],
                                ['SHA-256', versi.checksum],
                                ['Diunggah', formatTanggal(versi.dibuat_pada, true)],
                                ['Diterbitkan', formatTanggal(versi.diterbitkan_pada, true)],
                                ['Oleh', versi.diterbitkan_oleh],
                            ].map(([k, v]) => (
                                <div key={String(k)} className="flex justify-between gap-2 border-b pb-1">
                                    <dt className="text-muted-foreground">{k}</dt>
                                    <dd className="text-right font-medium break-all">{v ?? '—'}</dd>
                                </div>
                            ))}
                        </dl>
                        {versi.url_publik && (
                            <Button asChild variant="outline" size="sm" className="mt-4">
                                <a href={versi.url_publik} target="_blank" rel="noopener">
                                    <ExternalLink /> Lihat halaman publik
                                </a>
                            </Button>
                        )}
                    </KartuAdmin>
                    <KartuAdmin judul="Riwayat versi lingkup ini">
                        <ul className="space-y-2 text-sm">
                            {riwayat.map((r) => (
                                <li key={r.id} className="flex items-center justify-between gap-2">
                                    <Link href={`/admin/dokumen/${r.id}`} className={r.id === versi.id ? 'font-bold' : 'hover:text-primary'}>
                                        v{r.nomor_versi} {r.aktif && '(aktif)'}
                                    </Link>
                                    <LencanaStatus status={r.status} />
                                </li>
                            ))}
                        </ul>
                    </KartuAdmin>
                </div>
            </div>
        </HalamanAdmin>
    );
}
