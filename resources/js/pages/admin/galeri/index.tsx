import { Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { Halaman, KepalaUrut, KotakCari, LencanaStatus, PilihFilter, terapkanFilter } from '@/components/admin/daftar';
import { HalamanAdmin } from '@/components/admin/halaman-admin';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatTanggal } from '@/lib/utils';
import type { Paginasi } from '@/types';

type G = { id: number; judul: string; status: string; tanggal: string | null; urutan: number; gambar: string | null; izin: boolean };

export default function DaftarGaleri({
    galeri,
    filter,
}: {
    galeri: Paginasi<G>;
    filter: { q: string; urut: string; arah: string; per: number; status: string | null };
}) {
    const url = '/admin/galeri';

    return (
        <HalamanAdmin
            judul="Galeri"
            jejak={[{ judul: 'Galeri' }]}
            deskripsi="Item baru dibuat sebagai draf. Dokumentasi anak hanya diterbitkan bila dasar izin publikasi diisi."
            aksi={
                <Button asChild>
                    <Link href="/admin/galeri/buat">
                        <Plus /> Item baru
                    </Link>
                </Button>
            }
        >
            <KotakCari url={url} filter={filter} placeholder="Cari judul…">
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
                {galeri.data.length ? (
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Gambar</TableHead>
                                <TableHead>
                                    <KepalaUrut url={url} filter={filter} kolom="judul">
                                        Judul
                                    </KepalaUrut>
                                </TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Izin publikasi</TableHead>
                                <TableHead>
                                    <KepalaUrut url={url} filter={filter} kolom="tanggal_kegiatan">
                                        Tanggal
                                    </KepalaUrut>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {galeri.data.map((g) => (
                                <TableRow key={g.id}>
                                    <TableCell>{g.gambar && <img src={g.gambar} alt="" className="size-12 rounded object-cover" />}</TableCell>
                                    <TableCell>
                                        <Link href={`/admin/galeri/${g.id}/edit`} className="font-semibold hover:text-primary">
                                            {g.judul}
                                        </Link>
                                    </TableCell>
                                    <TableCell>
                                        <LencanaStatus status={g.status} />
                                    </TableCell>
                                    <TableCell className="text-sm">{g.izin ? 'Tercatat' : <span className="text-amber-700">Belum diisi</span>}</TableCell>
                                    <TableCell className="text-sm">{formatTanggal(g.tanggal)}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                ) : (
                    <p className="rounded-xl border border-dashed p-10 text-center text-sm text-muted-foreground">
                        Galeri kosong. Bagian galeri di beranda otomatis disembunyikan sampai ada item terbit.
                    </p>
                )}
                <Halaman data={galeri} />
            </div>
        </HalamanAdmin>
    );
}
