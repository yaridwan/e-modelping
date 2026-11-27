import { Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { Halaman, KepalaUrut, KotakCari, LencanaStatus, PilihFilter, terapkanFilter } from '@/components/admin/daftar';
import { HalamanAdmin } from '@/components/admin/halaman-admin';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatTanggal } from '@/lib/utils';
import type { Paginasi } from '@/types';

type H = { id: number; kode: string; judul: string; slug: string; status: string; inti: boolean; url: string; diperbarui_pada: string | null };

export default function DaftarHalaman({
    halaman,
    filter,
}: {
    halaman: Paginasi<H>;
    filter: { q: string; urut: string; arah: string; per: number; status: string | null };
}) {
    const url = '/admin/halaman';

    return (
        <HalamanAdmin
            judul="Halaman Informasi"
            jejak={[{ judul: 'Halaman Informasi' }]}
            deskripsi="Tentang, panduan, kontak, privasi, dan halaman tambahan. Halaman inti tidak dapat dihapus."
            aksi={
                <Button asChild>
                    <Link href="/admin/halaman/buat">
                        <Plus /> Halaman baru
                    </Link>
                </Button>
            }
        >
            <KotakCari url={url} filter={filter} placeholder="Cari judul halaman…">
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
                                <KepalaUrut url={url} filter={filter} kolom="judul">
                                    Judul
                                </KepalaUrut>
                            </TableHead>
                            <TableHead>URL</TableHead>
                            <TableHead>
                                <KepalaUrut url={url} filter={filter} kolom="status">
                                    Status
                                </KepalaUrut>
                            </TableHead>
                            <TableHead>
                                <KepalaUrut url={url} filter={filter} kolom="diperbarui_pada">
                                    Diperbarui
                                </KepalaUrut>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {halaman.data.map((h) => (
                            <TableRow key={h.id}>
                                <TableCell>
                                    <Link href={`/admin/halaman/${h.id}/edit`} className="font-semibold hover:text-primary">
                                        {h.judul}
                                    </Link>{' '}
                                    {h.inti && <Badge variant="outline">Inti</Badge>}
                                </TableCell>
                                <TableCell className="font-mono text-xs">{h.url}</TableCell>
                                <TableCell>
                                    <LencanaStatus status={h.status} />
                                </TableCell>
                                <TableCell className="text-sm text-muted-foreground">{formatTanggal(h.diperbarui_pada)}</TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
                <Halaman data={halaman} />
            </div>
        </HalamanAdmin>
    );
}
