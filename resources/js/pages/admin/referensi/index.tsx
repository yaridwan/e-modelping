import { useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { BidangPilih, BidangSakelar, BidangTeks, BidangTextarea } from '@/components/admin/bidang';
import { Halaman, KepalaUrut, KotakCari, PilihFilter, terapkanFilter } from '@/components/admin/daftar';
import { DialogForm } from '@/components/admin/dialog-form';
import { HalamanAdmin } from '@/components/admin/halaman-admin';
import { TombolKonfirmasi } from '@/components/admin/konfirmasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { Paginasi } from '@/types';

type R = {
    id: number;
    penulis: string;
    tahun: number | null;
    judul: string;
    jenis_sumber: string;
    nama_penerbit_jurnal: string | null;
    volume: string | null;
    nomor_terbitan: string | null;
    halaman: string | null;
    doi: string | null;
    url: string | null;
    tanggal_akses: string | null;
    catatan_editorial: string | null;
    status_verifikasi: string;
    urutan: number;
    aktif: boolean;
};
const KOSONG = {
    penulis: '',
    tahun: '',
    judul: '',
    jenis_sumber: 'jurnal',
    nama_penerbit_jurnal: '',
    volume: '',
    nomor_terbitan: '',
    halaman: '',
    doi: '',
    url: '',
    tanggal_akses: '',
    catatan_editorial: '',
    status_verifikasi: 'belum_diverifikasi',
    urutan: 0,
    aktif: true,
};
const STATUS = [
    { nilai: 'belum_diverifikasi', label: 'Belum diverifikasi' },
    { nilai: 'tercantum_di_sumber', label: 'Tercantum di sumber' },
    { nilai: 'terverifikasi', label: 'Terverifikasi' },
    { nilai: 'perlu_koreksi', label: 'Perlu koreksi' },
];

export default function KelolaReferensi({
    referensi,
    filter,
}: {
    referensi: Paginasi<R>;
    filter: { q: string; urut: string; arah: string; per: number; status_verifikasi: string };
}) {
    const [edit, setEdit] = useState<R | 'baru' | null>(null);
    const form = useForm<typeof KOSONG>(KOSONG);
    const url = '/admin/referensi';
    const buka = (r: R | 'baru') => {
        setEdit(r);
        form.clearErrors();
        form.setData(
            r === 'baru'
                ? KOSONG
                : ({
                      ...KOSONG,
                      ...Object.fromEntries(Object.entries(r).map(([k, v]) => [k, v ?? ''])),
                      tahun: r.tahun ? String(r.tahun) : '',
                      aktif: r.aktif,
                  } as typeof KOSONG),
        );
    };

    return (
        <HalamanAdmin
            judul="Referensi Pustaka"
            jejak={[{ judul: 'Referensi' }]}
            deskripsi="Isi DOI/URL hanya bila diketahui dari sumber. Jangan mengarang DOI. Ketidakselarasan tahun dicatat pada catatan editorial."
            aksi={
                <Button onClick={() => buka('baru')}>
                    <Plus /> Referensi baru
                </Button>
            }
        >
            <KotakCari url={url} filter={filter} placeholder="Cari penulis atau judul…">
                <PilihFilter
                    label="Verifikasi"
                    nilai={filter.status_verifikasi}
                    onUbah={(v) => terapkanFilter(url, { ...filter, status_verifikasi: v })}
                    opsi={STATUS}
                />
            </KotakCari>
            <div className="mt-4">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>
                                <KepalaUrut url={url} filter={filter} kolom="penulis">
                                    Penulis
                                </KepalaUrut>
                            </TableHead>
                            <TableHead>
                                <KepalaUrut url={url} filter={filter} kolom="tahun">
                                    Tahun
                                </KepalaUrut>
                            </TableHead>
                            <TableHead>Judul</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="text-right">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {referensi.data.map((r) => (
                            <TableRow key={r.id}>
                                <TableCell className="max-w-48 text-sm">{r.penulis}</TableCell>
                                <TableCell>{r.tahun ?? '—'}</TableCell>
                                <TableCell className="max-w-md text-sm">
                                    {r.judul}
                                    {r.catatan_editorial && <span className="mt-1 block text-xs text-amber-700">⚠ {r.catatan_editorial}</span>}
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline">{STATUS.find((s) => s.nilai === r.status_verifikasi)?.label}</Badge>
                                </TableCell>
                                <TableCell className="space-x-1 text-right whitespace-nowrap">
                                    <Button variant="outline" size="sm" onClick={() => buka(r)}>
                                        <Pencil />
                                    </Button>
                                    <TombolKonfirmasi url={`/admin/referensi/${r.id}`} judul="Hapus referensi?" deskripsi="Referensi dihapus permanen.">
                                        <Trash2 />
                                    </TombolKonfirmasi>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
                <Halaman data={referensi} />
            </div>
            <DialogForm buka={edit !== null} onTutup={() => setEdit(null)} judul={edit === 'baru' ? 'Referensi baru' : 'Sunting referensi'}>
                <form
                    className="grid gap-3 sm:grid-cols-2"
                    onSubmit={(e) => {
                        e.preventDefault();
                        const opsi = { preserveScroll: true, onSuccess: () => setEdit(null) };
                        if (edit === 'baru') {
                            form.post('/admin/referensi', opsi);
                        } else if (edit) {
                            form.put(`/admin/referensi/${edit.id}`, opsi);
                        }
                    }}
                >
                    <BidangTeks
                        id="penulis"
                        label="Penulis"
                        wajib
                        className="sm:col-span-2"
                        nilai={form.data.penulis}
                        onUbah={(v) => form.setData('penulis', v)}
                        galat={form.errors.penulis}
                    />
                    <BidangTeks
                        id="tahun"
                        label="Tahun"
                        tipe="number"
                        nilai={form.data.tahun}
                        onUbah={(v) => form.setData('tahun', v)}
                        galat={form.errors.tahun}
                    />
                    <BidangPilih
                        id="jenis"
                        label="Jenis sumber"
                        nilai={form.data.jenis_sumber}
                        onUbah={(v) => form.setData('jenis_sumber', v)}
                        opsi={['buku', 'bab_buku', 'jurnal', 'laporan', 'laman', 'lainnya'].map((j) => ({ nilai: j, label: j.replace('_', ' ') }))}
                    />
                    <BidangTextarea
                        id="judul"
                        label="Judul"
                        wajib
                        className="sm:col-span-2"
                        baris={2}
                        nilai={form.data.judul}
                        onUbah={(v) => form.setData('judul', v)}
                        galat={form.errors.judul}
                    />
                    <BidangTeks
                        id="penerbit"
                        label="Penerbit/jurnal"
                        className="sm:col-span-2"
                        nilai={form.data.nama_penerbit_jurnal}
                        onUbah={(v) => form.setData('nama_penerbit_jurnal', v)}
                    />
                    <BidangTeks id="volume" label="Volume" nilai={form.data.volume} onUbah={(v) => form.setData('volume', v)} />
                    <BidangTeks id="nomor" label="Nomor" nilai={form.data.nomor_terbitan} onUbah={(v) => form.setData('nomor_terbitan', v)} />
                    <BidangTeks id="halaman" label="Halaman" nilai={form.data.halaman} onUbah={(v) => form.setData('halaman', v)} />
                    <BidangTeks
                        id="doi"
                        label="DOI (bila diketahui)"
                        placeholder="10.xxxx/…"
                        nilai={form.data.doi}
                        onUbah={(v) => form.setData('doi', v)}
                        galat={form.errors.doi}
                    />
                    <BidangTeks
                        id="url"
                        label="URL (bila diketahui)"
                        className="sm:col-span-2"
                        nilai={form.data.url}
                        onUbah={(v) => form.setData('url', v)}
                        galat={form.errors.url}
                    />
                    <BidangTeks id="akses" label="Tanggal akses" tipe="date" nilai={form.data.tanggal_akses} onUbah={(v) => form.setData('tanggal_akses', v)} />
                    <BidangPilih
                        id="status"
                        label="Status verifikasi"
                        nilai={form.data.status_verifikasi}
                        onUbah={(v) => form.setData('status_verifikasi', v)}
                        opsi={STATUS}
                    />
                    <BidangTextarea
                        id="catatan"
                        label="Catatan editorial"
                        className="sm:col-span-2"
                        baris={2}
                        nilai={form.data.catatan_editorial}
                        onUbah={(v) => form.setData('catatan_editorial', v)}
                    />
                    <BidangSakelar id="aktif" label="Tampilkan di halaman Tentang" nilai={form.data.aktif} onUbah={(v) => form.setData('aktif', v)} />
                    <Button type="submit" className="sm:col-span-2" disabled={form.processing}>
                        Simpan
                    </Button>
                </form>
            </DialogForm>
        </HalamanAdmin>
    );
}
