import { Link, router } from '@inertiajs/react';
import { BookOpen, Download, Eye, FileCheck2, HardDrive, Inbox, Layers, RefreshCw, Users } from 'lucide-react';
import type { ReactNode } from 'react';
import { lazy } from 'react';
import { LencanaDemo, LencanaStatus } from '@/components/admin/daftar';
import { HalamanAdmin, KartuAdmin } from '@/components/admin/halaman-admin';
import { KlienSaja } from '@/components/umum/klien-saja';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { cn, formatAngka, formatTanggal, formatUkuran } from '@/lib/utils';

const Grafik = {
    Tren: lazy(() => import('@/components/admin/grafik').then((m) => ({ default: m.GrafikTren }))),
    Batang: lazy(() => import('@/components/admin/grafik').then((m) => ({ default: m.GrafikBatang }))),
    Donat: lazy(() => import('@/components/admin/grafik').then((m) => ({ default: m.GrafikDonat }))),
};

type Props = {
    filter: { hari: number; dari: string; sampai: string; demo: boolean; ada_demo: boolean };
    widget: Record<string, number | string | null>;
    grafik: {
        kunjungan: { tanggal: string; kunjungan: number; pengunjung: number }[];
        dokumen: { tanggal: string; baca: number; unduh: number }[];
        bab_populer: { label: string; judul: string; kunjungan: number }[];
        status_konten: { status: string; bab: number; subbab: number }[];
        jenis_media: { jenis: string; jumlah: number }[];
    };
    tabel: {
        dokumen: { id: number; judul: string; nomor_versi: string; status: string; aktif: boolean; bab: string; diperbarui_pada: string | null }[];
        pesan: { id: number; nama: string; subjek: string; status: string; demo: boolean; dibuat_pada: string | null }[];
        aktivitas: { id: number; aksi: string; ringkasan: string; pengguna: string | null; dibuat_pada: string | null }[];
        inventaris: { tabel: string; label: string; modul: string; total: number; rincian: Record<string, number> }[];
    };
    kesehatan: Record<string, string | number | boolean>;
};

function Widget({ ikon, label, nilai, keterangan, href }: { ikon: ReactNode; label: string; nilai: ReactNode; keterangan?: ReactNode; href?: string }) {
    const isi = (
        <div className="h-full rounded-xl border bg-card p-4 transition-colors hover:border-primary/40">
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                <span className="flex size-8 items-center justify-center rounded-lg bg-secondary text-secondary-foreground">{ikon}</span>
                {label}
            </div>
            <p className="mt-3 text-2xl font-extrabold tabular-nums">{nilai}</p>
            {keterangan && <p className="mt-1 text-xs text-muted-foreground">{keterangan}</p>}
        </div>
    );

    return href ? <Link href={href}>{isi}</Link> : isi;
}

const MuatGrafik = () => <Skeleton className="h-64 w-full" />;

export default function Dasbor({ filter, widget: w, grafik, tabel, kesehatan }: Props) {
    const ubah = (data: { hari?: number; demo?: boolean }) => {
        const demo = data.demo ?? filter.demo;
        router.get('/admin', { hari: data.hari ?? filter.hari, ...(demo ? { demo: 1 } : {}) }, { preserveScroll: true, preserveState: true });
    };

    return (
        <HalamanAdmin
            judul="Dashboard"
            deskripsi="Ringkasan konten dan statistik aplikasi nyata. Aktivitas kunjungan bukan bukti peningkatan karakter anak atau efektivitas penelitian."
            aksi={
                <div className="flex flex-wrap items-center gap-2">
                    <div className="inline-flex rounded-lg border bg-card p-0.5" role="group" aria-label="Periode">
                        {[7, 30, 90].map((h) => (
                            <button
                                key={h}
                                type="button"
                                onClick={() => ubah({ hari: h })}
                                aria-pressed={filter.hari === h}
                                className={cn('rounded-md px-3 py-1.5 text-sm', filter.hari === h ? 'bg-primary text-primary-foreground' : 'hover:bg-accent')}
                            >
                                {h} hari
                            </button>
                        ))}
                    </div>
                    {filter.ada_demo && (
                        <label className="flex items-center gap-2 rounded-lg border bg-card px-3 py-1.5 text-sm">
                            <input type="checkbox" checked={filter.demo} onChange={(e) => ubah({ demo: e.target.checked })} />
                            Tampilkan data demo
                        </label>
                    )}
                </div>
            }
        >
            {filter.demo && (
                <div
                    className="mb-5 flex items-center gap-2 rounded-xl border border-violet-300 bg-violet-50 p-3 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950/40 dark:text-violet-200"
                    role="status"
                >
                    <LencanaDemo /> Mode demo: angka statistik di bawah berasal dari data demonstrasi (data_contoh) dan tidak bercampur dengan data nyata.
                </div>
            )}
            <p className="mb-4 text-sm text-muted-foreground">
                Periode {formatTanggal(filter.dari)} – {formatTanggal(filter.sampai)} (WIB)
            </p>
            <div className="grid grid-cols-2 gap-3 lg:grid-cols-4 xl:grid-cols-5">
                <Widget ikon={<BookOpen className="size-4" />} label="Bab terbit" nilai={`${w.bab_terbit} / ${w.bab_total}`} href="/admin/bab" />
                <Widget ikon={<Layers className="size-4" />} label="Subbab terbit" nilai={`${w.subbab_terbit} / ${w.subbab_total}`} href="/admin/bab" />
                <Widget ikon={<FileCheck2 className="size-4" />} label="PDF aktif" nilai={formatAngka(Number(w.pdf_aktif))} href="/admin/dokumen" />
                <Widget ikon={<HardDrive className="size-4" />} label="Ukuran media" nilai={formatUkuran(Number(w.ukuran_media))} href="/admin/media" />
                <Widget ikon={<Inbox className="size-4" />} label="Pesan baru" nilai={formatAngka(Number(w.pesan_baru))} href="/admin/pesan?status=baru" />
                <Widget
                    ikon={<Eye className="size-4" />}
                    label="Kunjungan"
                    nilai={formatAngka(Number(w.kunjungan))}
                    keterangan="Tampilan halaman unik per hari"
                />
                <Widget
                    ikon={<Users className="size-4" />}
                    label="Pengunjung unik"
                    nilai={formatAngka(Number(w.pengunjung_unik_perkiraan))}
                    keterangan="Perkiraan, bukan orang absolut"
                />
                <Widget
                    ikon={<BookOpen className="size-4" />}
                    label="Baca PDF"
                    nilai={formatAngka(Number(w.baca_dokumen))}
                    keterangan="Range lanjutan tidak dihitung"
                />
                <Widget
                    ikon={<Download className="size-4" />}
                    label="Unduhan dilayani"
                    nilai={formatAngka(Number(w.unduh_dokumen))}
                    keterangan="Respons server, bukan bukti tersimpan"
                />
                <Widget
                    ikon={<RefreshCw className="size-4" />}
                    label="Materi diperbarui"
                    nilai={<span className="text-base">{formatTanggal(w.materi_diperbarui as string | null)}</span>}
                />
            </div>

            <div className="mt-6 grid gap-5 xl:grid-cols-2">
                <KartuAdmin judul="Tren kunjungan harian" deskripsi="Kunjungan dan pengunjung unik perkiraan">
                    <KlienSaja fallback={<MuatGrafik />}>
                        <Grafik.Tren
                            judul="Grafik tren kunjungan harian"
                            data={grafik.kunjungan}
                            seri={[
                                { kunci: 'kunjungan', label: 'Kunjungan', warna: 'var(--chart-1)' },
                                { kunci: 'pengunjung', label: 'Pengunjung unik', warna: 'var(--chart-3)' },
                            ]}
                        />
                    </KlienSaja>
                </KartuAdmin>
                <KartuAdmin judul="Bab paling sering dibuka">
                    <KlienSaja fallback={<MuatGrafik />}>
                        <Grafik.Batang
                            judul="Grafik bab paling sering dibuka"
                            data={grafik.bab_populer}
                            kunciLabel="label"
                            horizontal
                            seri={[{ kunci: 'kunjungan', label: 'Kunjungan', warna: 'var(--chart-1)' }]}
                        />
                    </KlienSaja>
                </KartuAdmin>
                <KartuAdmin judul="Baca & unduh dokumen per hari">
                    <KlienSaja fallback={<MuatGrafik />}>
                        <Grafik.Batang
                            judul="Grafik baca dan unduh dokumen"
                            data={grafik.dokumen}
                            kunciLabel="tanggal"
                            seri={[
                                { kunci: 'baca', label: 'Baca', warna: 'var(--chart-2)' },
                                { kunci: 'unduh', label: 'Unduh', warna: 'var(--chart-4)' },
                            ]}
                        />
                    </KlienSaja>
                </KartuAdmin>
                <KartuAdmin judul="Jenis media">
                    <KlienSaja fallback={<MuatGrafik />}>
                        <Grafik.Donat
                            judul="Grafik distribusi jenis media"
                            data={grafik.jenis_media.map((j) => ({
                                nama: j.jenis === 'video_eksternal' ? 'Video' : j.jenis === 'pdf' ? 'PDF' : 'Gambar',
                                jumlah: j.jumlah,
                            }))}
                        />
                    </KlienSaja>
                </KartuAdmin>
            </div>

            <div className="mt-6 grid gap-5 xl:grid-cols-2">
                <KartuAdmin judul="Status konten" deskripsi="Alternatif tabel status bab dan subbab">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Bab</TableHead>
                                <TableHead className="text-right">Subbab</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {grafik.status_konten.map((s) => (
                                <TableRow key={s.status}>
                                    <TableCell>{s.status}</TableCell>
                                    <TableCell className="text-right tabular-nums">{s.bab}</TableCell>
                                    <TableCell className="text-right tabular-nums">{s.subbab}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </KartuAdmin>
                <KartuAdmin judul="Bab populer" deskripsi="Tabel pendamping grafik">
                    {grafik.bab_populer.length ? (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Bab</TableHead>
                                    <TableHead className="text-right">Kunjungan</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {grafik.bab_populer.map((b) => (
                                    <TableRow key={b.label}>
                                        <TableCell>
                                            {b.label} · {b.judul}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">{formatAngka(b.kunjungan)}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    ) : (
                        <p className="text-sm text-muted-foreground">Belum ada kunjungan bab pada periode ini.</p>
                    )}
                </KartuAdmin>
                <KartuAdmin
                    judul="Dokumen aktif & terbaru"
                    aksi={
                        <Link href="/admin/dokumen" className="text-sm text-primary hover:underline">
                            Semua
                        </Link>
                    }
                >
                    <Table>
                        <TableBody>
                            {tabel.dokumen.map((d) => (
                                <TableRow key={d.id}>
                                    <TableCell>
                                        <Link href={`/admin/dokumen/${d.id}`} className="font-medium hover:text-primary">
                                            {d.bab}
                                        </Link>
                                        <span className="block text-xs text-muted-foreground">
                                            v{d.nomor_versi}
                                            {d.aktif && ' · aktif'}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <LencanaStatus status={d.status} />
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </KartuAdmin>
                <KartuAdmin
                    judul="Pesan terbaru"
                    aksi={
                        <Link href="/admin/pesan" className="text-sm text-primary hover:underline">
                            Semua
                        </Link>
                    }
                >
                    {tabel.pesan.length ? (
                        <Table>
                            <TableBody>
                                {tabel.pesan.map((p) => (
                                    <TableRow key={p.id}>
                                        <TableCell>
                                            <Link href={`/admin/pesan/${p.id}`} className="font-medium hover:text-primary">
                                                {p.subjek}
                                            </Link>
                                            <span className="block text-xs text-muted-foreground">
                                                {p.nama} · {formatTanggal(p.dibuat_pada)}
                                            </span>
                                        </TableCell>
                                        <TableCell className="space-x-1 text-right">
                                            {p.demo && <LencanaDemo />}
                                            <LencanaStatus status={p.status} />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    ) : (
                        <p className="text-sm text-muted-foreground">Belum ada pesan.</p>
                    )}
                </KartuAdmin>
            </div>

            <KartuAdmin
                className="mt-6"
                judul="Aktivitas admin terbaru"
                aksi={
                    <Link href="/admin/log-aktivitas" className="text-sm text-primary hover:underline">
                        Log lengkap
                    </Link>
                }
            >
                <ul className="divide-y text-sm">
                    {tabel.aktivitas.map((a) => (
                        <li key={a.id} className="flex flex-wrap justify-between gap-2 py-2">
                            <span>
                                <span className="font-mono text-xs text-muted-foreground">{a.aksi}</span> · {a.ringkasan}
                            </span>
                            <span className="text-xs text-muted-foreground">
                                {a.pengguna ?? 'Sistem'} · {formatTanggal(a.dibuat_pada, true)}
                            </span>
                        </li>
                    ))}
                </ul>
            </KartuAdmin>

            <KartuAdmin
                className="mt-6"
                judul="Inventaris data seluruh tabel"
                deskripsi="Jumlah data dan rincian status per tabel domain. Token pemulihan hanya ditampilkan sebagai jumlah."
            >
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Tabel</TableHead>
                            <TableHead>Modul</TableHead>
                            <TableHead className="text-right">Jumlah</TableHead>
                            <TableHead>Rincian</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {tabel.inventaris.map((t) => (
                            <TableRow key={t.tabel}>
                                <TableCell>
                                    <span className="font-medium">{t.label}</span>
                                    <span className="block font-mono text-xs text-muted-foreground">{t.tabel}</span>
                                </TableCell>
                                <TableCell className="text-sm">{t.modul}</TableCell>
                                <TableCell className="text-right font-semibold tabular-nums">{formatAngka(t.total)}</TableCell>
                                <TableCell className="text-xs text-muted-foreground">
                                    {Object.entries(t.rincian)
                                        .map(([k, v]) => `${k}: ${v}`)
                                        .join(' · ') || '—'}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </KartuAdmin>

            <KartuAdmin className="mt-6" judul="Panel kesehatan teknis">
                <dl className="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2 lg:grid-cols-3">
                    {Object.entries(kesehatan).map(([k, v]) => (
                        <div key={k} className="flex justify-between gap-3 border-b py-1.5">
                            <dt className="text-muted-foreground">{k.replaceAll('_', ' ')}</dt>
                            <dd className="font-mono text-xs">{typeof v === 'boolean' ? (v ? 'ya' : 'tidak') : String(v)}</dd>
                        </div>
                    ))}
                </dl>
            </KartuAdmin>
        </HalamanAdmin>
    );
}
