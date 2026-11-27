import { router } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { lazy, useState } from 'react';
import { LencanaDemo } from '@/components/admin/daftar';
import { HalamanAdmin, KartuAdmin } from '@/components/admin/halaman-admin';
import { KlienSaja } from '@/components/umum/klien-saja';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatAngka, formatTanggal } from '@/lib/utils';

const GrafikTren = lazy(() => import('@/components/admin/grafik').then((m) => ({ default: m.GrafikTren })));
const GrafikDonat = lazy(() => import('@/components/admin/grafik').then((m) => ({ default: m.GrafikDonat })));

type Props = {
    filter: { dari: string; sampai: string; demo: boolean; ada_demo: boolean };
    ringkasan: { kunjungan: number; pengunjung_unik_perkiraan: number; baca_dokumen: number; unduh_dokumen: number };
    kunjungan: { tanggal: string; kunjungan: number; pengunjung: number }[];
    dokumen: { tanggal: string; baca: number; unduh: number }[];
    bab_populer: { label: string; judul: string; kunjungan: number }[];
    dokumen_populer: { versi_id: number; judul: string; nomor_versi: string; baca: number; unduh: number }[];
    halaman_populer: { halaman: string; jumlah: number }[];
    perangkat: { nama: string; jumlah: number }[];
    kebijakan: { aktif: boolean; kecualikan_admin: boolean; kecualikan_bot: boolean; retensi_hari: number; zona_waktu: string };
};

export default function Statistik(p: Props) {
    const [dari, setDari] = useState(p.filter.dari);
    const [sampai, setSampai] = useState(p.filter.sampai);
    const terapkan = (demo = p.filter.demo) =>
        router.get('/admin/statistik', { dari, sampai, ...(demo ? { demo: 1 } : {}) }, { preserveScroll: true, preserveState: true });
    const ekspor = `/admin/statistik/ekspor?${new URLSearchParams({ dari: p.filter.dari, sampai: p.filter.sampai, ...(p.filter.demo ? { demo: '1' } : {}) })}`;

    return (
        <HalamanAdmin
            judul="Statistik"
            jejak={[{ judul: 'Statistik' }]}
            deskripsi="Statistik pihak pertama tanpa pelacak eksternal. Angka kunjungan bukan bukti peningkatan karakter anak."
            aksi={
                <Button asChild variant="outline">
                    <a href={ekspor}>
                        <Download /> Ekspor CSV
                    </a>
                </Button>
            }
        >
            <KartuAdmin>
                <form
                    className="flex flex-wrap items-end gap-3"
                    onSubmit={(e) => {
                        e.preventDefault();
                        terapkan();
                    }}
                >
                    <div className="grid gap-1.5">
                        <Label htmlFor="dari">Dari</Label>
                        <Input id="dari" type="date" value={dari} onChange={(e) => setDari(e.target.value)} max={sampai} />
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="sampai">Sampai</Label>
                        <Input id="sampai" type="date" value={sampai} onChange={(e) => setSampai(e.target.value)} min={dari} />
                    </div>
                    <Button type="submit">Terapkan</Button>
                    {p.filter.ada_demo && (
                        <label className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={p.filter.demo} onChange={(e) => terapkan(e.target.checked)} /> Data demo
                        </label>
                    )}
                </form>
                <p className="mt-3 text-xs text-muted-foreground">
                    Tanggal mengikuti zona {p.kebijakan.zona_waktu}. Pencatatan {p.kebijakan.aktif ? 'aktif' : 'NONAKTIF'}; admin{' '}
                    {p.kebijakan.kecualikan_admin ? 'dikecualikan' : 'dihitung'}; bot {p.kebijakan.kecualikan_bot ? 'dikecualikan' : 'dihitung'}; retensi{' '}
                    {p.kebijakan.retensi_hari} hari.
                </p>
            </KartuAdmin>
            {p.filter.demo && (
                <p className="mt-4 flex items-center gap-2 text-sm text-violet-800 dark:text-violet-200">
                    <LencanaDemo /> Menampilkan data demonstrasi saja.
                </p>
            )}
            <dl className="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
                {[
                    ['Kunjungan', p.ringkasan.kunjungan, 'Tampilan halaman unik per pengunjung/jalur/hari'],
                    ['Pengunjung unik (perkiraan)', p.ringkasan.pengunjung_unik_perkiraan, 'Sidik pseudonim berbeda'],
                    ['Baca dokumen', p.ringkasan.baca_dokumen, 'Satu kali per pengunjung/versi/hari'],
                    ['Unduhan dilayani', p.ringkasan.unduh_dokumen, 'Respons server, bukan bukti tersimpan'],
                ].map(([l, n, k]) => (
                    <div key={String(l)} className="rounded-xl border bg-card p-4">
                        <dt className="text-sm text-muted-foreground">{l}</dt>
                        <dd className="mt-1 text-2xl font-extrabold tabular-nums">{formatAngka(Number(n))}</dd>
                        <dd className="text-xs text-muted-foreground">{k}</dd>
                    </div>
                ))}
            </dl>
            <div className="mt-6 grid gap-5 xl:grid-cols-[2fr_1fr]">
                <KartuAdmin judul="Kunjungan harian">
                    <KlienSaja fallback={<Skeleton className="h-64" />}>
                        <GrafikTren
                            judul="Grafik kunjungan harian"
                            data={p.kunjungan}
                            seri={[
                                { kunci: 'kunjungan', label: 'Kunjungan', warna: 'var(--chart-1)' },
                                { kunci: 'pengunjung', label: 'Pengunjung unik', warna: 'var(--chart-3)' },
                            ]}
                        />
                    </KlienSaja>
                </KartuAdmin>
                <KartuAdmin judul="Perangkat">
                    <KlienSaja fallback={<Skeleton className="h-64" />}>
                        <GrafikDonat judul="Grafik distribusi perangkat" data={p.perangkat} />
                    </KlienSaja>
                </KartuAdmin>
                <KartuAdmin judul="Baca & unduh harian">
                    <KlienSaja fallback={<Skeleton className="h-64" />}>
                        <GrafikTren
                            judul="Grafik baca dan unduh dokumen"
                            data={p.dokumen}
                            seri={[
                                { kunci: 'baca', label: 'Baca', warna: 'var(--chart-2)' },
                                { kunci: 'unduh', label: 'Unduh', warna: 'var(--chart-4)' },
                            ]}
                        />
                    </KlienSaja>
                </KartuAdmin>
                <KartuAdmin judul="Halaman populer">
                    <ul className="space-y-1 text-sm">
                        {p.halaman_populer.map((h) => (
                            <li key={h.halaman} className="flex justify-between gap-2">
                                <span className="truncate font-mono text-xs">{h.halaman}</span>
                                <span className="tabular-nums">{formatAngka(h.jumlah)}</span>
                            </li>
                        ))}
                        {!p.halaman_populer.length && <li className="text-muted-foreground">Belum ada data.</li>}
                    </ul>
                </KartuAdmin>
            </div>
            <div className="mt-6 grid gap-5 xl:grid-cols-2">
                <KartuAdmin judul="Tabel kunjungan harian" deskripsi="Alternatif tabel untuk grafik">
                    <div className="max-h-80 overflow-y-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Tanggal</TableHead>
                                    <TableHead className="text-right">Kunjungan</TableHead>
                                    <TableHead className="text-right">Pengunjung</TableHead>
                                    <TableHead className="text-right">Baca</TableHead>
                                    <TableHead className="text-right">Unduh</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {p.kunjungan.map((k, i) => (
                                    <TableRow key={k.tanggal}>
                                        <TableCell>{formatTanggal(k.tanggal)}</TableCell>
                                        <TableCell className="text-right tabular-nums">{k.kunjungan}</TableCell>
                                        <TableCell className="text-right tabular-nums">{k.pengunjung}</TableCell>
                                        <TableCell className="text-right tabular-nums">{p.dokumen[i]?.baca ?? 0}</TableCell>
                                        <TableCell className="text-right tabular-nums">{p.dokumen[i]?.unduh ?? 0}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </KartuAdmin>
                <div className="space-y-5">
                    <KartuAdmin judul="Bab populer">
                        <Table>
                            <TableBody>
                                {p.bab_populer.map((b) => (
                                    <TableRow key={b.label}>
                                        <TableCell>
                                            {b.label} · {b.judul}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">{formatAngka(b.kunjungan)}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </KartuAdmin>
                    <KartuAdmin judul="Dokumen populer">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Dokumen</TableHead>
                                    <TableHead className="text-right">Baca</TableHead>
                                    <TableHead className="text-right">Unduh</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {p.dokumen_populer.map((d) => (
                                    <TableRow key={d.versi_id}>
                                        <TableCell className="text-sm">
                                            {d.judul} <span className="text-muted-foreground">v{d.nomor_versi}</span>
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">{d.baca}</TableCell>
                                        <TableCell className="text-right tabular-nums">{d.unduh}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </KartuAdmin>
                </div>
            </div>
        </HalamanAdmin>
    );
}
