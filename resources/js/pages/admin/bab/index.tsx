import { Link } from '@inertiajs/react';
import { Eye, Pencil } from 'lucide-react';
import { Halaman, KepalaUrut, KotakCari, LencanaStatus, PilihFilter, terapkanFilter } from '@/components/admin/daftar';
import { HalamanAdmin } from '@/components/admin/halaman-admin';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatTanggal } from '@/lib/utils';
import type { Paginasi } from '@/types';

type Bab = {
    id: number;
    nomor: number;
    romawi: string;
    judul: string;
    slug: string;
    status: string;
    subbab: number;
    subbab_terbit: number;
    versi_aktif: string | null;
    diperbarui_pada: string | null;
};
type Filter = { q: string; urut: string; arah: string; per: number; status: string | null };

export default function DaftarBab({ bab, filter }: { bab: Paginasi<Bab>; filter: Filter }) {
    const url = '/admin/bab';

    return (
        <HalamanAdmin
            judul="Bab & Subbab"
            jejak={[{ judul: 'Bab & Subbab' }]}
            deskripsi="Sembilan bab inti dijaga: bab tidak dapat dibuat atau dihapus, tetapi isi, subbab, status, dan SEO dapat dikelola."
        >
            <KotakCari url={url} filter={filter} placeholder="Cari judul atau ringkasan bab…">
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
            </KotakCari>
            <div className="mt-4">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>
                                <KepalaUrut url={url} filter={filter} kolom="urutan">
                                    No
                                </KepalaUrut>
                            </TableHead>
                            <TableHead>
                                <KepalaUrut url={url} filter={filter} kolom="judul">
                                    Judul
                                </KepalaUrut>
                            </TableHead>
                            <TableHead>
                                <KepalaUrut url={url} filter={filter} kolom="status">
                                    Status
                                </KepalaUrut>
                            </TableHead>
                            <TableHead>Subbab terbit</TableHead>
                            <TableHead>PDF aktif</TableHead>
                            <TableHead>
                                <KepalaUrut url={url} filter={filter} kolom="diperbarui_pada">
                                    Diperbarui
                                </KepalaUrut>
                            </TableHead>
                            <TableHead className="text-right">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {bab.data.map((b) => (
                            <TableRow key={b.id}>
                                <TableCell className="font-bold">{b.romawi}</TableCell>
                                <TableCell>
                                    <Link href={`/admin/bab/${b.id}/edit`} className="font-semibold hover:text-primary">
                                        {b.judul}
                                    </Link>
                                    <span className="block font-mono text-xs text-muted-foreground">/modul/{b.slug}</span>
                                </TableCell>
                                <TableCell>
                                    <LencanaStatus status={b.status} />
                                </TableCell>
                                <TableCell className="tabular-nums">
                                    {b.subbab_terbit} / {b.subbab}
                                </TableCell>
                                <TableCell>{b.versi_aktif ? `v${b.versi_aktif}` : <span className="text-muted-foreground">—</span>}</TableCell>
                                <TableCell className="text-sm text-muted-foreground">{formatTanggal(b.diperbarui_pada)}</TableCell>
                                <TableCell className="space-x-1 text-right whitespace-nowrap">
                                    <Button asChild variant="ghost" size="sm">
                                        <Link href={`/admin/bab/${b.id}/pratinjau`}>
                                            <Eye /> Pratinjau
                                        </Link>
                                    </Button>
                                    <Button asChild variant="outline" size="sm">
                                        <Link href={`/admin/bab/${b.id}/edit`}>
                                            <Pencil /> Kelola
                                        </Link>
                                    </Button>
                                </TableCell>
                            </TableRow>
                        ))}
                        {!bab.data.length && (
                            <TableRow>
                                <TableCell colSpan={7} className="py-10 text-center text-muted-foreground">
                                    Tidak ada bab yang cocok dengan filter.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
                <Halaman data={bab} />
            </div>
        </HalamanAdmin>
    );
}
