import { router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { BidangSakelar, BidangTeks, BidangTextarea } from '@/components/admin/bidang';
import { Halaman, KotakCari, PilihFilter, terapkanFilter } from '@/components/admin/daftar';
import { DialogForm } from '@/components/admin/dialog-form';
import { HalamanAdmin } from '@/components/admin/halaman-admin';
import { TombolKonfirmasi } from '@/components/admin/konfirmasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Paginasi } from '@/types';

type F = { id: number; kategori: string; pertanyaan: string; jawaban: string; urutan: number; aktif: boolean };

export default function KelolaFaq({
    faq,
    filter,
    semua_id,
}: {
    faq: Paginasi<F>;
    filter: { q: string; urut: string; arah: string; per: number; aktif: string | null };
    semua_id: number[];
}) {
    const [edit, setEdit] = useState<F | null | 'baru'>(null);
    const form = useForm({ kategori: 'Umum', pertanyaan: '', jawaban: '', aktif: true });
    const buka = (f: F | 'baru') => {
        setEdit(f);
        form.clearErrors();
        form.setData(
            f === 'baru'
                ? { kategori: 'Umum', pertanyaan: '', jawaban: '', aktif: true }
                : { kategori: f.kategori, pertanyaan: f.pertanyaan, jawaban: f.jawaban, aktif: f.aktif },
        );
    };
    const geser = (id: number, arah: -1 | 1) => {
        const x = [...semua_id];
        const i = x.indexOf(id);
        if (i + arah < 0 || i + arah >= x.length) {
            return;
        }
        [x[i], x[i + arah]] = [x[i + arah], x[i]];
        router.post('/admin/faq/urutan', { id: x }, { preserveScroll: true });
    };
    const url = '/admin/faq';

    return (
        <HalamanAdmin
            judul="FAQ"
            jejak={[{ judul: 'FAQ' }]}
            deskripsi="Pertanyaan umum yang tampil di beranda dan halaman FAQ (FAQ schema hanya untuk pertanyaan yang tampil)."
            aksi={
                <Button onClick={() => buka('baru')}>
                    <Plus /> Pertanyaan baru
                </Button>
            }
        >
            <KotakCari url={url} filter={filter} placeholder="Cari pertanyaan/jawaban…">
                <PilihFilter
                    label="Tampil"
                    nilai={filter.aktif}
                    onUbah={(v) => terapkanFilter(url, { ...filter, aktif: v })}
                    opsi={[
                        { nilai: '1', label: 'Aktif' },
                        { nilai: '0', label: 'Nonaktif' },
                    ]}
                />
            </KotakCari>
            <ul className="mt-4 space-y-2">
                {faq.data.map((f) => (
                    <li key={f.id} className="flex items-start gap-3 rounded-xl border bg-card p-4">
                        <div className="flex flex-col">
                            <Button variant="ghost" size="icon" onClick={() => geser(f.id, -1)} aria-label="Naikkan">
                                <ArrowUp />
                            </Button>
                            <Button variant="ghost" size="icon" onClick={() => geser(f.id, 1)} aria-label="Turunkan">
                                <ArrowDown />
                            </Button>
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="text-xs text-muted-foreground">
                                {f.kategori} {!f.aktif && <Badge variant="outline">Nonaktif</Badge>}
                            </p>
                            <p className="font-semibold">{f.pertanyaan}</p>
                            <p className="mt-1 line-clamp-2 text-sm text-muted-foreground">{f.jawaban}</p>
                        </div>
                        <Button variant="outline" size="sm" onClick={() => buka(f)}>
                            <Pencil /> Sunting
                        </Button>
                        <TombolKonfirmasi url={`/admin/faq/${f.id}`} judul="Hapus pertanyaan?" deskripsi="Pertanyaan dihapus permanen.">
                            <Trash2 />
                        </TombolKonfirmasi>
                    </li>
                ))}
            </ul>
            {!faq.data.length && <p className="mt-6 text-sm text-muted-foreground">Belum ada pertanyaan.</p>}
            <Halaman data={faq} />
            <DialogForm buka={edit !== null} onTutup={() => setEdit(null)} judul={edit === 'baru' ? 'Pertanyaan baru' : 'Sunting pertanyaan'}>
                <form
                    className="grid gap-4"
                    onSubmit={(e) => {
                        e.preventDefault();
                        const opsi = { preserveScroll: true, onSuccess: () => setEdit(null) };
                        if (edit === 'baru') {
                            form.post('/admin/faq', opsi);
                        } else if (edit) {
                            form.put(`/admin/faq/${edit.id}`, opsi);
                        }
                    }}
                >
                    <BidangTeks
                        id="kategori"
                        label="Kategori"
                        wajib
                        nilai={form.data.kategori}
                        onUbah={(v) => form.setData('kategori', v)}
                        galat={form.errors.kategori}
                    />
                    <BidangTeks
                        id="pertanyaan"
                        label="Pertanyaan"
                        wajib
                        nilai={form.data.pertanyaan}
                        onUbah={(v) => form.setData('pertanyaan', v)}
                        galat={form.errors.pertanyaan}
                    />
                    <BidangTextarea
                        id="jawaban"
                        label="Jawaban"
                        wajib
                        baris={5}
                        nilai={form.data.jawaban}
                        onUbah={(v) => form.setData('jawaban', v)}
                        galat={form.errors.jawaban}
                    />
                    <BidangSakelar id="aktif" label="Tampilkan" nilai={form.data.aktif} onUbah={(v) => form.setData('aktif', v)} />
                    <Button type="submit" disabled={form.processing}>
                        Simpan
                    </Button>
                </form>
            </DialogForm>
        </HalamanAdmin>
    );
}
