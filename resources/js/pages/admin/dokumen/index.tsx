import { Link } from '@inertiajs/react';
import { Upload } from 'lucide-react';
import { Halaman, KepalaUrut, KotakCari, LencanaStatus, PilihFilter, terapkanFilter } from '@/components/admin/daftar';
import { HalamanAdmin } from '@/components/admin/halaman-admin';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatTanggal, formatUkuran } from '@/lib/utils';
import type { Paginasi } from '@/types';

export type VersiRingkas = {
    id: number;
    jenis_dokumen: string;
    lingkup: string;
    bab_id: number | null;
    bab_terbit: boolean;
    judul: string;
    nomor_versi: string;
    status: string;
    aktif: boolean;
    izinkan_unduh: boolean;
    ukuran_bita: number | null;
    jumlah_halaman: number | null;
    diterbitkan_pada: string | null;
    dibuat_pada: string | null;
    diperbarui_pada: string | null;
};

export default function DaftarDokumen({
    versi,
    filter,
    bab,
    izin_unduh_global,
}: {
    versi: Paginasi<VersiRingkas>;
    filter: { q: string; urut: string; arah: string; per: number; status: string | null; lingkup: string };
    bab: { id: number; label: string }[];
    izin_unduh_global: boolean;
}) {
    const url = '/admin/dokumen';

    return (
        <HalamanAdmin
            judul="Dokumen PDF"
            jejak={[{ judul: 'Dokumen PDF' }]}
            deskripsi="Unggah PDF per bab atau modul lengkap sebagai draf, tinjau, lalu terbitkan. Hanya satu versi aktif per lingkup; versi sebelumnya otomatis diarsipkan."
            aksi={
                <Button asChild>
                    <Link href="/admin/dokumen/unggah">
                        <Upload /> Unggah PDF
                    </Link>
                </Button>
            }
        >
            {!izin_unduh_global && (
                <p className="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
                    Izin unduh global sedang nonaktif: semua tombol & endpoint unduh publik ditutup (pembaca tetap dapat membaca).
                </p>
            )}
            <KotakCari url={url} filter={filter} placeholder="Cari judul atau nomor versi…">
                <PilihFilter
                    label="Status"
                    nilai={filter.status}
                    onUbah={(v) => terapkanFilter(url, { ...filter, status: v })}
                    opsi={[
                        { nilai: 'draf', label: 'Draf' },
                        { nilai: 'terbit', label: 'Terbit' },
                        { nilai: 'arsip', label: 'Arsip' },
                    ]}
                />
                <PilihFilter
                    label="Lingkup"
                    nilai={filter.lingkup}
                    onUbah={(v) => terapkanFilter(url, { ...filter, lingkup: v })}
                    opsi={[{ nilai: 'modul_lengkap', label: 'Modul lengkap' }, ...bab.map((b) => ({ nilai: String(b.id), label: b.label }))]}
                />
            </KotakCari>
            <div className="mt-4">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Lingkup</TableHead>
                            <TableHead>
                                <KepalaUrut url={url} filter={filter} kolom="nomor_versi">
                                    Versi
                                </KepalaUrut>
                            </TableHead>
                            <TableHead>
                                <KepalaUrut url={url} filter={filter} kolom="status">
                                    Status
                                </KepalaUrut>
                            </TableHead>
                            <TableHead>Berkas</TableHead>
                            <TableHead>Unduh</TableHead>
                            <TableHead>
                                <KepalaUrut url={url} filter={filter} kolom="diperbarui_pada">
                                    Diperbarui
                                </KepalaUrut>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {versi.data.map((v) => (
                            <TableRow key={v.id}>
                                <TableCell>
                                    <Link href={`/admin/dokumen/${v.id}`} className="font-semibold hover:text-primary">
                                        {v.lingkup}
                                    </Link>
                                    <span className="block text-xs text-muted-foreground">{v.judul}</span>
                                </TableCell>
                                <TableCell className="font-mono">
                                    v{v.nomor_versi}{' '}
                                    {v.aktif && (
                                        <span className="ml-1 rounded bg-primary px-1.5 py-0.5 text-[0.65rem] font-bold text-primary-foreground">AKTIF</span>
                                    )}
                                </TableCell>
                                <TableCell>
                                    <LencanaStatus status={v.status} />
                                    {v.aktif && !v.bab_terbit && <span className="block text-xs text-amber-700">Bab belum terbit</span>}
                                </TableCell>
                                <TableCell className="text-sm">
                                    {formatUkuran(v.ukuran_bita)} · {v.jumlah_halaman ?? '?'} hlm
                                </TableCell>
                                <TableCell className="text-sm">{v.izinkan_unduh ? 'Diizinkan' : 'Ditutup'}</TableCell>
                                <TableCell className="text-sm text-muted-foreground">{formatTanggal(v.diperbarui_pada)}</TableCell>
                            </TableRow>
                        ))}
                        {!versi.data.length && (
                            <TableRow>
                                <TableCell colSpan={6} className="py-10 text-center text-muted-foreground">
                                    Belum ada dokumen yang cocok.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
                <Halaman data={versi} />
            </div>
        </HalamanAdmin>
    );
}
