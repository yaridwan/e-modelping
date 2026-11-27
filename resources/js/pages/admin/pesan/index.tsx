import { Link, router } from '@inertiajs/react';
import {
    Archive,
    ArchiveRestore,
    CheckCheck,
    CircleCheckBig,
    Copy,
    Download,
    Eye,
    Mail,
    MailOpen,
    MoreHorizontal,
    NotebookPen,
    Reply,
    Trash2,
    X,
} from 'lucide-react';
import { useState } from 'react';
import type { Konfirmasi, StatusPesan } from '@/components/admin/aksi-pesan';
import { DialogKonfirmasi, salinTeks, tautanBalas, ubahStatus } from '@/components/admin/aksi-pesan';
import { Halaman, KeadaanKosong, KepalaUrut, KotakCari, LencanaDemo, LencanaStatus, terapkanFilter } from '@/components/admin/daftar';
import { HalamanAdmin } from '@/components/admin/halaman-admin';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { cn, formatTanggal } from '@/lib/utils';
import type { Paginasi } from '@/types';

type P = {
    id: number;
    nama: string;
    surel: string;
    institusi: string | null;
    subjek: string;
    cuplikan: string;
    status: StatusPesan;
    catatan: boolean;
    demo: boolean;
    dibuat_pada: string | null;
};
type Filter = { q: string; urut: string; arah: string; per: number; status: string | null; demo: boolean };
type AksiMassal = StatusPesan | 'hapus';

const URL_DAFTAR = '/admin/pesan';

export default function DaftarPesan({
    pesan,
    filter,
    jumlah,
    ada_demo,
}: {
    pesan: Paginasi<P>;
    filter: Filter;
    jumlah: Record<StatusPesan, number>;
    ada_demo: boolean;
}) {
    const [pilihan, setPilihan] = useState<number[]>([]);
    const [konfirmasi, setKonfirmasi] = useState<Konfirmasi | null>(null);
    const [proses, setProses] = useState(false);

    const idHalaman = pesan.data.map((p) => p.id);
    // Pilihan hanya berlaku untuk baris yang sedang tampil (berganti halaman/filter = pilihan gugur).
    const terpilih = pilihan.filter((id) => idHalaman.includes(id));
    const semuaTerpilih = idHalaman.length > 0 && terpilih.length === idHalaman.length;
    const diArsip = filter.status === 'arsip';

    const tab: { nilai: string | null; label: string; n: number }[] = [
        { nilai: null, label: 'Kotak masuk', n: jumlah.baru + jumlah.dibaca + jumlah.ditindaklanjuti },
        { nilai: 'baru', label: 'Baru', n: jumlah.baru },
        { nilai: 'dibaca', label: 'Dibaca', n: jumlah.dibaca },
        { nilai: 'ditindaklanjuti', label: 'Ditindaklanjuti', n: jumlah.ditindaklanjuti },
        { nilai: 'arsip', label: 'Arsip', n: jumlah.arsip },
        { nilai: 'semua', label: 'Semua', n: jumlah.baru + jumlah.dibaca + jumlah.ditindaklanjuti + jumlah.arsip },
    ];

    const ekspor = `/admin/pesan/ekspor?${new URLSearchParams({ ...(filter.status ? { status: filter.status } : {}), ...(filter.demo ? { demo: '1' } : {}), ...(filter.q ? { q: filter.q } : {}) })}`;

    const alihkan = (id: number) => setPilihan((s) => (s.includes(id) ? s.filter((x) => x !== id) : [...s, id]));

    const kirimMassal = (aksi: AksiMassal, selesai?: () => void) => {
        router.post(
            '/admin/pesan/massal',
            { id: terpilih, aksi },
            {
                preserveScroll: true,
                onStart: () => setProses(true),
                onSuccess: () => setPilihan([]),
                onFinish: () => {
                    setProses(false);
                    selesai?.();
                },
            },
        );
    };

    const hapusSatu = (p: P) =>
        setKonfirmasi({
            judul: 'Hapus pesan ini secara permanen?',
            deskripsi: (
                <>
                    Pesan “{p.subjek}” dari {p.nama} akan dihapus permanen dan <strong>tidak dapat dipulihkan</strong>. Jika hanya ingin merapikan kotak masuk,
                    gunakan Arsipkan.
                </>
            ),
            labelAksi: 'Hapus permanen',
            jalankan: (selesai) => router.delete(`/admin/pesan/${p.id}`, { preserveScroll: true, onFinish: selesai }),
        });

    const hapusMassal = () =>
        setKonfirmasi({
            judul: `Hapus ${terpilih.length} pesan secara permanen?`,
            deskripsi: (
                <>
                    {terpilih.length} pesan terpilih akan dihapus permanen dan <strong>tidak dapat dipulihkan</strong>. Pertimbangkan mengarsipkan atau
                    mengekspor CSV terlebih dahulu.
                </>
            ),
            labelAksi: 'Hapus permanen',
            jalankan: (selesai) => kirimMassal('hapus', selesai),
        });

    return (
        <HalamanAdmin
            judul="Pesan Masuk"
            jejak={[{ judul: 'Pesan Masuk' }]}
            deskripsi="Pesan dari formulir kontak. Tidak tampil publik dan tidak ada balasan otomatis — balas lewat aplikasi surel, lalu tandai sudah ditindaklanjuti."
            aksi={
                <Button asChild variant="outline">
                    <a href={ekspor}>
                        <Download /> Ekspor CSV
                    </a>
                </Button>
            }
        >
            <nav aria-label="Saring status pesan" className="mb-4 flex flex-wrap gap-2">
                {tab.map((t) => {
                    const aktif = (filter.status ?? null) === t.nilai;

                    return (
                        <button
                            key={t.label}
                            type="button"
                            aria-pressed={aktif}
                            onClick={() => terapkanFilter(URL_DAFTAR, { ...filter, status: t.nilai })}
                            className={cn(
                                'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm transition-colors',
                                aktif ? 'border-primary bg-primary text-primary-foreground' : 'bg-card hover:bg-accent',
                            )}
                        >
                            {t.label}
                            <span className={cn('rounded-full px-1.5 text-xs font-bold tabular-nums', aktif ? 'bg-primary-foreground/20' : 'bg-muted')}>
                                {t.n}
                            </span>
                        </button>
                    );
                })}
            </nav>

            <KotakCari url={URL_DAFTAR} filter={filter} placeholder="Cari nama, surel, institusi, atau subjek…">
                <label className="flex items-center gap-2 text-sm text-muted-foreground">
                    Tampilkan
                    <select
                        value={filter.per}
                        onChange={(e) => terapkanFilter(URL_DAFTAR, { ...filter, per: Number(e.target.value) })}
                        className="h-9 rounded-md border border-input bg-background px-2 text-sm text-foreground shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                    >
                        {[10, 15, 25, 50].map((n) => (
                            <option key={n} value={n}>
                                {n} / halaman
                            </option>
                        ))}
                    </select>
                </label>
                {ada_demo && (
                    <label className="flex items-center gap-2 text-sm">
                        <input type="checkbox" checked={filter.demo} onChange={(e) => terapkanFilter(URL_DAFTAR, { ...filter, demo: e.target.checked })} /> Data
                        demo
                    </label>
                )}
            </KotakCari>

            {/* Bilah aksi massal */}
            <div
                className={cn(
                    'sticky top-2 z-10 mt-4 flex flex-wrap items-center gap-2 rounded-xl border bg-card p-2 shadow-sm transition-opacity',
                    terpilih.length ? 'opacity-100' : 'pointer-events-none hidden opacity-0',
                )}
                role="toolbar"
                aria-label="Aksi untuk pesan terpilih"
            >
                <span className="px-2 text-sm font-semibold" aria-live="polite">
                    {terpilih.length} dipilih
                </span>
                <Button size="sm" variant="outline" disabled={proses} onClick={() => kirimMassal('dibaca')}>
                    <MailOpen /> Tandai dibaca
                </Button>
                <Button size="sm" variant="outline" disabled={proses} onClick={() => kirimMassal('baru')}>
                    <Mail /> Tandai belum dibaca
                </Button>
                <Button size="sm" variant="outline" disabled={proses} onClick={() => kirimMassal('ditindaklanjuti')}>
                    <CircleCheckBig /> Ditindaklanjuti
                </Button>
                {diArsip ? (
                    <Button size="sm" variant="outline" disabled={proses} onClick={() => kirimMassal('dibaca')}>
                        <ArchiveRestore /> Pulihkan
                    </Button>
                ) : (
                    <Button size="sm" variant="outline" disabled={proses} onClick={() => kirimMassal('arsip')}>
                        <Archive /> Arsipkan
                    </Button>
                )}
                <Button size="sm" variant="outline" className="text-destructive hover:text-destructive" disabled={proses} onClick={hapusMassal}>
                    <Trash2 /> Hapus
                </Button>
                <Button size="sm" variant="ghost" className="ml-auto" onClick={() => setPilihan([])}>
                    <X /> Batal pilih
                </Button>
            </div>

            <div className="mt-4">
                {pesan.data.length ? (
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-10">
                                    <Checkbox
                                        aria-label="Pilih semua pesan di halaman ini"
                                        checked={semuaTerpilih ? true : terpilih.length ? 'indeterminate' : false}
                                        onCheckedChange={() => setPilihan(semuaTerpilih ? [] : idHalaman)}
                                    />
                                </TableHead>
                                <TableHead>
                                    <KepalaUrut url={URL_DAFTAR} filter={filter} kolom="subjek">
                                        Pesan
                                    </KepalaUrut>
                                </TableHead>
                                <TableHead>
                                    <KepalaUrut url={URL_DAFTAR} filter={filter} kolom="nama_pengirim">
                                        Pengirim
                                    </KepalaUrut>
                                </TableHead>
                                <TableHead>
                                    <KepalaUrut url={URL_DAFTAR} filter={filter} kolom="status">
                                        Status
                                    </KepalaUrut>
                                </TableHead>
                                <TableHead>
                                    <KepalaUrut url={URL_DAFTAR} filter={filter} kolom="dibuat_pada">
                                        Diterima
                                    </KepalaUrut>
                                </TableHead>
                                <TableHead className="w-12 text-right">
                                    <span className="sr-only">Aksi</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {pesan.data.map((p) => {
                                const baru = p.status === 'baru';
                                const dipilih = terpilih.includes(p.id);

                                return (
                                    <TableRow key={p.id} data-state={dipilih ? 'selected' : undefined} className={cn(baru && 'bg-primary/[0.04]')}>
                                        <TableCell>
                                            <Checkbox aria-label={`Pilih pesan “${p.subjek}”`} checked={dipilih} onCheckedChange={() => alihkan(p.id)} />
                                        </TableCell>
                                        <TableCell className="max-w-md min-w-64 whitespace-normal">
                                            <Link href={`/admin/pesan/${p.id}`} className="group block">
                                                <span className={cn('flex items-center gap-2 group-hover:text-primary', baru ? 'font-bold' : 'font-medium')}>
                                                    {baru && <span className="size-2 shrink-0 rounded-full bg-primary" aria-label="Belum dibaca" />}
                                                    <span className="line-clamp-1">{p.subjek}</span>
                                                    {p.catatan && (
                                                        <NotebookPen className="size-3.5 shrink-0 text-muted-foreground" aria-label="Ada catatan internal" />
                                                    )}
                                                </span>
                                                <span className="mt-0.5 line-clamp-2 text-xs text-muted-foreground">{p.cuplikan}</span>
                                            </Link>
                                        </TableCell>
                                        <TableCell className="text-sm whitespace-normal">
                                            <span className={cn(baru && 'font-semibold')}>{p.nama}</span>
                                            <span className="block text-xs text-muted-foreground">{p.surel}</span>
                                            {p.institusi && <span className="block text-xs text-muted-foreground">{p.institusi}</span>}
                                        </TableCell>
                                        <TableCell className="space-x-1">
                                            <LencanaStatus status={p.status} />
                                            {p.demo && <LencanaDemo />}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">{formatTanggal(p.dibuat_pada, true)}</TableCell>
                                        <TableCell className="text-right">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" size="icon" aria-label={`Aksi untuk pesan “${p.subjek}”`}>
                                                        <MoreHorizontal />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end" className="w-56">
                                                    <DropdownMenuItem asChild>
                                                        <Link href={`/admin/pesan/${p.id}`}>
                                                            <Eye /> Buka pesan
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem asChild>
                                                        <a href={tautanBalas({ surel: p.surel, subjek: p.subjek })}>
                                                            <Reply /> Balas lewat surel
                                                        </a>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem onSelect={() => void salinTeks(p.surel, 'Alamat surel')}>
                                                        <Copy /> Salin alamat surel
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    {baru ? (
                                                        <DropdownMenuItem onSelect={() => ubahStatus(p.id, 'dibaca')}>
                                                            <MailOpen /> Tandai dibaca
                                                        </DropdownMenuItem>
                                                    ) : (
                                                        <DropdownMenuItem onSelect={() => ubahStatus(p.id, 'baru')}>
                                                            <Mail /> Tandai belum dibaca
                                                        </DropdownMenuItem>
                                                    )}
                                                    {p.status !== 'ditindaklanjuti' && (
                                                        <DropdownMenuItem onSelect={() => ubahStatus(p.id, 'ditindaklanjuti')}>
                                                            <CheckCheck /> Tandai ditindaklanjuti
                                                        </DropdownMenuItem>
                                                    )}
                                                    {p.status === 'arsip' ? (
                                                        <DropdownMenuItem onSelect={() => ubahStatus(p.id, 'dibaca')}>
                                                            <ArchiveRestore /> Pulihkan ke kotak masuk
                                                        </DropdownMenuItem>
                                                    ) : (
                                                        <DropdownMenuItem onSelect={() => ubahStatus(p.id, 'arsip')}>
                                                            <Archive /> Arsipkan
                                                        </DropdownMenuItem>
                                                    )}
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem variant="destructive" onSelect={() => hapusSatu(p)}>
                                                        <Trash2 /> Hapus permanen…
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>
                ) : (
                    <KeadaanKosong
                        judul={filter.q ? 'Tidak ada pesan yang cocok' : diArsip ? 'Arsip kosong' : 'Belum ada pesan'}
                        deskripsi={filter.q ? 'Coba kata kunci lain atau pilih tab “Semua”.' : 'Pesan dari formulir kontak publik akan muncul di sini.'}
                    />
                )}
                <Halaman data={pesan} />
            </div>

            <DialogKonfirmasi konfirmasi={konfirmasi} tutup={() => setKonfirmasi(null)} />
        </HalamanAdmin>
    );
}
