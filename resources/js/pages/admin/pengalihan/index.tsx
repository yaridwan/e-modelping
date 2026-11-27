import { useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { BidangPilih, BidangSakelar, BidangTeks } from '@/components/admin/bidang';
import { Halaman, KotakCari } from '@/components/admin/daftar';
import { DialogForm } from '@/components/admin/dialog-form';
import { HalamanAdmin } from '@/components/admin/halaman-admin';
import { TombolKonfirmasi } from '@/components/admin/konfirmasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatTanggal } from '@/lib/utils';
import type { Paginasi } from '@/types';

type P = { id: number; jalur_lama: string; jalur_baru: string; kode_status: number; aktif: boolean; diperbarui_pada: string | null };

export default function KelolaPengalihan({ pengalihan, filter }: { pengalihan: Paginasi<P>; filter: { q: string; urut: string; arah: string; per: number } }) {
    const [edit, setEdit] = useState<P | 'baru' | null>(null);
    const form = useForm({ jalur_lama: '', jalur_baru: '', kode_status: '301', aktif: true });
    const buka = (p: P | 'baru') => {
        setEdit(p);
        form.clearErrors();
        form.setData(
            p === 'baru'
                ? { jalur_lama: '', jalur_baru: '', kode_status: '301', aktif: true }
                : { jalur_lama: p.jalur_lama, jalur_baru: p.jalur_baru, kode_status: String(p.kode_status), aktif: p.aktif },
        );
    };

    return (
        <HalamanAdmin
            judul="Pengalihan URL"
            jejak={[{ judul: 'Pengalihan URL' }]}
            deskripsi="Pengalihan dibuat otomatis saat slug berubah. Hanya jalur internal; loop dan rantai panjang ditolak."
            aksi={
                <Button onClick={() => buka('baru')}>
                    <Plus /> Pengalihan baru
                </Button>
            }
        >
            <KotakCari url="/admin/pengalihan" filter={filter} placeholder="Cari jalur…" />
            <div className="mt-4">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Jalur lama</TableHead>
                            <TableHead>Jalur baru</TableHead>
                            <TableHead>Kode</TableHead>
                            <TableHead>Diperbarui</TableHead>
                            <TableHead className="text-right">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {pengalihan.data.map((p) => (
                            <TableRow key={p.id}>
                                <TableCell className="font-mono text-xs">{p.jalur_lama}</TableCell>
                                <TableCell className="font-mono text-xs">{p.jalur_baru}</TableCell>
                                <TableCell>
                                    {p.kode_status} {!p.aktif && <Badge variant="outline">Nonaktif</Badge>}
                                </TableCell>
                                <TableCell className="text-sm">{formatTanggal(p.diperbarui_pada)}</TableCell>
                                <TableCell className="space-x-1 text-right">
                                    <Button variant="outline" size="sm" onClick={() => buka(p)}>
                                        <Pencil />
                                    </Button>
                                    <TombolKonfirmasi
                                        url={`/admin/pengalihan/${p.id}`}
                                        judul="Hapus pengalihan?"
                                        deskripsi="URL lama akan menghasilkan 404 bila tidak ada konten."
                                    >
                                        <Trash2 />
                                    </TombolKonfirmasi>
                                </TableCell>
                            </TableRow>
                        ))}
                        {!pengalihan.data.length && (
                            <TableRow>
                                <TableCell colSpan={5} className="py-8 text-center text-muted-foreground">
                                    Belum ada pengalihan.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
                <Halaman data={pengalihan} />
            </div>
            <DialogForm buka={edit !== null} onTutup={() => setEdit(null)} judul={edit === 'baru' ? 'Pengalihan baru' : 'Sunting pengalihan'}>
                <form
                    className="grid gap-3"
                    onSubmit={(e) => {
                        e.preventDefault();
                        const opsi = { preserveScroll: true, onSuccess: () => setEdit(null) };
                        if (edit === 'baru') {
                            form.post('/admin/pengalihan', opsi);
                        } else if (edit) {
                            form.put(`/admin/pengalihan/${edit.id}`, opsi);
                        }
                    }}
                >
                    <BidangTeks
                        id="lama"
                        label="Jalur lama"
                        wajib
                        placeholder="/modul/slug-lama"
                        nilai={form.data.jalur_lama}
                        onUbah={(v) => form.setData('jalur_lama', v)}
                        galat={form.errors.jalur_lama}
                    />
                    <BidangTeks
                        id="baru"
                        label="Jalur baru"
                        wajib
                        placeholder="/modul/slug-baru"
                        nilai={form.data.jalur_baru}
                        onUbah={(v) => form.setData('jalur_baru', v)}
                        galat={form.errors.jalur_baru}
                    />
                    <BidangPilih
                        id="kode"
                        label="Kode status"
                        nilai={form.data.kode_status}
                        onUbah={(v) => form.setData('kode_status', v)}
                        opsi={[
                            { nilai: '301', label: '301 Permanen' },
                            { nilai: '308', label: '308 Permanen (metode tetap)' },
                            { nilai: '302', label: '302 Sementara' },
                        ]}
                    />
                    <BidangSakelar id="aktif" label="Aktif" nilai={form.data.aktif} onUbah={(v) => form.setData('aktif', v)} />
                    <Button type="submit" disabled={form.processing}>
                        Simpan
                    </Button>
                </form>
            </DialogForm>
        </HalamanAdmin>
    );
}
