import { router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { BidangPilih, BidangSakelar, BidangTeks } from '@/components/admin/bidang';
import { DialogForm } from '@/components/admin/dialog-form';
import { HalamanAdmin, KartuAdmin } from '@/components/admin/halaman-admin';
import { TombolKonfirmasi } from '@/components/admin/konfirmasi';
import { IkonSosial } from '@/components/umum/ikon';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type M = {
    id: number;
    induk_id: number | null;
    lokasi: string;
    label: string;
    jenis_tautan: string;
    halaman_id: number | null;
    bab_id: number | null;
    url: string | null;
    ikon: string | null;
    buka_tab_baru: boolean;
    urutan: number;
    aktif: boolean;
    tujuan: string | null;
    target_label: string | null;
};
type S = { id: number; nama_platform: string; label: string; url: string | null; urutan: number; aktif: boolean };
type Opsi = { id: number; label: string };

const KOSONG_MENU = {
    lokasi: 'utama',
    label: '',
    jenis_tautan: 'internal',
    halaman_id: '',
    bab_id: '',
    url: '',
    induk_id: '',
    ikon: '',
    buka_tab_baru: false,
    aktif: true,
};

export default function KelolaMenu({
    menu,
    sosial,
    halaman,
    bab,
    ikon,
    platform,
}: {
    menu: M[];
    sosial: S[];
    halaman: Opsi[];
    bab: Opsi[];
    ikon: string[];
    platform: string[];
}) {
    const [edit, setEdit] = useState<M | 'baru' | null>(null);
    const [editSosial, setEditSosial] = useState<S | 'baru' | null>(null);
    const form = useForm(KOSONG_MENU);
    const fs = useForm({ nama_platform: 'instagram', label: '', url: '', urutan: 0, aktif: false });
    const bukaMenu = (m: M | 'baru', lokasi = 'utama') => {
        setEdit(m);
        form.clearErrors();
        form.setData(
            m === 'baru'
                ? { ...KOSONG_MENU, lokasi }
                : {
                      lokasi: m.lokasi,
                      label: m.label,
                      jenis_tautan: m.jenis_tautan,
                      halaman_id: m.halaman_id ? String(m.halaman_id) : '',
                      bab_id: m.bab_id ? String(m.bab_id) : '',
                      url: m.url ?? '',
                      induk_id: m.induk_id ? String(m.induk_id) : '',
                      ikon: m.ikon ?? '',
                      buka_tab_baru: m.buka_tab_baru,
                      aktif: m.aktif,
                  },
        );
    };
    const bukaSosial = (s: S | 'baru') => {
        setEditSosial(s);
        fs.clearErrors();
        fs.setData(
            s === 'baru'
                ? { nama_platform: 'instagram', label: '', url: '', urutan: 0, aktif: false }
                : { nama_platform: s.nama_platform, label: s.label, url: s.url ?? '', urutan: s.urutan, aktif: s.aktif },
        );
    };
    const geser = (lokasi: string, id: number, arah: -1 | 1) => {
        const ids = menu.filter((m) => m.lokasi === lokasi).map((m) => m.id);
        const i = ids.indexOf(id);
        if (i + arah < 0 || i + arah >= ids.length) {
            return;
        }
        [ids[i], ids[i + arah]] = [ids[i + arah], ids[i]];
        router.post('/admin/menu/urutan', { lokasi, id: ids }, { preserveScroll: true });
    };

    return (
        <HalamanAdmin
            judul="Menu & Tautan Sosial"
            jejak={[{ judul: 'Menu & Tautan Sosial' }]}
            deskripsi="Tautan ke halaman/bab draf otomatis disembunyikan dari publik. Protokol berbahaya ditolak."
        >
            <div className="grid gap-6 xl:grid-cols-2">
                {['utama', 'footer'].map((lokasi) => (
                    <KartuAdmin
                        key={lokasi}
                        judul={lokasi === 'utama' ? 'Menu utama (header)' : 'Menu footer'}
                        aksi={
                            <Button size="sm" onClick={() => bukaMenu('baru', lokasi)}>
                                <Plus /> Tambah
                            </Button>
                        }
                    >
                        <ul className="space-y-2">
                            {menu
                                .filter((m) => m.lokasi === lokasi)
                                .map((m) => (
                                    <li key={m.id} className={`flex items-center gap-2 rounded-lg border p-2 ${m.induk_id ? 'ml-6' : ''}`}>
                                        <div className="min-w-0 flex-1">
                                            <p className="font-semibold">
                                                {m.label} {!m.aktif && <Badge variant="outline">Nonaktif</Badge>}{' '}
                                                {m.aktif && !m.tujuan && <Badge variant="outline">Tersembunyi (target belum terbit)</Badge>}
                                            </p>
                                            <p className="truncate font-mono text-xs text-muted-foreground">
                                                {m.jenis_tautan}: {m.target_label ?? m.tujuan}
                                            </p>
                                        </div>
                                        <Button variant="ghost" size="icon" onClick={() => geser(lokasi, m.id, -1)} aria-label="Naikkan">
                                            <ArrowUp />
                                        </Button>
                                        <Button variant="ghost" size="icon" onClick={() => geser(lokasi, m.id, 1)} aria-label="Turunkan">
                                            <ArrowDown />
                                        </Button>
                                        <Button variant="outline" size="icon" onClick={() => bukaMenu(m)} aria-label={`Sunting ${m.label}`}>
                                            <Pencil />
                                        </Button>
                                        <TombolKonfirmasi
                                            url={`/admin/menu/${m.id}`}
                                            ukuran="icon"
                                            judul="Hapus menu?"
                                            deskripsi="Submenu di bawahnya ikut terhapus."
                                        >
                                            <Trash2 />
                                        </TombolKonfirmasi>
                                    </li>
                                ))}
                        </ul>
                    </KartuAdmin>
                ))}
            </div>
            <KartuAdmin
                className="mt-6"
                judul="Tautan sosial"
                deskripsi="Hanya akun milik pemilik website. Entri tanpa URL tetap nonaktif."
                aksi={
                    <Button size="sm" onClick={() => bukaSosial('baru')}>
                        <Plus /> Tambah
                    </Button>
                }
            >
                <ul className="grid gap-2 sm:grid-cols-2">
                    {sosial.map((s) => (
                        <li key={s.id} className="flex items-center gap-3 rounded-lg border p-3">
                            <IkonSosial platform={s.nama_platform} className="size-5" />
                            <div className="min-w-0 flex-1">
                                <p className="font-semibold">
                                    {s.label} {!s.aktif && <Badge variant="outline">Nonaktif</Badge>}
                                </p>
                                <p className="truncate text-xs text-muted-foreground">{s.url ?? 'URL belum diisi'}</p>
                            </div>
                            <Button variant="outline" size="icon" onClick={() => bukaSosial(s)} aria-label={`Sunting ${s.label}`}>
                                <Pencil />
                            </Button>
                            <TombolKonfirmasi
                                url={`/admin/tautan-sosial/${s.id}`}
                                ukuran="icon"
                                judul="Hapus tautan sosial?"
                                deskripsi="Tautan dihapus permanen."
                            >
                                <Trash2 />
                            </TombolKonfirmasi>
                        </li>
                    ))}
                </ul>
            </KartuAdmin>
            <DialogForm buka={edit !== null} onTutup={() => setEdit(null)} judul={edit === 'baru' ? 'Menu baru' : 'Sunting menu'}>
                <form
                    className="grid gap-3 sm:grid-cols-2"
                    onSubmit={(e) => {
                        e.preventDefault();
                        const opsi = { preserveScroll: true, onSuccess: () => setEdit(null) };
                        if (edit === 'baru') {
                            form.post('/admin/menu', opsi);
                        } else if (edit) {
                            form.put(`/admin/menu/${edit.id}`, opsi);
                        }
                    }}
                >
                    <BidangPilih
                        id="lokasi"
                        label="Lokasi"
                        nilai={form.data.lokasi}
                        onUbah={(v) => form.setData('lokasi', v)}
                        opsi={[
                            { nilai: 'utama', label: 'Utama' },
                            { nilai: 'footer', label: 'Footer' },
                        ]}
                    />
                    <BidangTeks id="label" label="Label" wajib nilai={form.data.label} onUbah={(v) => form.setData('label', v)} galat={form.errors.label} />
                    <BidangPilih
                        id="jenis"
                        label="Jenis tautan"
                        nilai={form.data.jenis_tautan}
                        onUbah={(v) => form.setData('jenis_tautan', v)}
                        opsi={[
                            { nilai: 'halaman', label: 'Halaman informasi' },
                            { nilai: 'bab', label: 'Bab modul' },
                            { nilai: 'internal', label: 'Jalur internal' },
                            { nilai: 'eksternal', label: 'URL eksternal' },
                        ]}
                    />
                    {form.data.jenis_tautan === 'halaman' && (
                        <BidangPilih
                            id="halaman_id"
                            label="Halaman"
                            kosong="Pilih…"
                            nilai={form.data.halaman_id}
                            onUbah={(v) => form.setData('halaman_id', v)}
                            opsi={halaman.map((h) => ({ nilai: h.id, label: h.label }))}
                            galat={form.errors.halaman_id}
                        />
                    )}
                    {form.data.jenis_tautan === 'bab' && (
                        <BidangPilih
                            id="bab_id"
                            label="Bab"
                            kosong="Pilih…"
                            nilai={form.data.bab_id}
                            onUbah={(v) => form.setData('bab_id', v)}
                            opsi={bab.map((b) => ({ nilai: b.id, label: b.label }))}
                            galat={form.errors.bab_id}
                        />
                    )}
                    {(form.data.jenis_tautan === 'internal' || form.data.jenis_tautan === 'eksternal') && (
                        <BidangTeks
                            id="url"
                            label={form.data.jenis_tautan === 'internal' ? 'Jalur (/…)' : 'URL (https://…)'}
                            wajib
                            nilai={form.data.url}
                            onUbah={(v) => form.setData('url', v)}
                            galat={form.errors.url}
                        />
                    )}
                    <BidangPilih
                        id="induk"
                        label="Induk (submenu)"
                        kosong="— Tingkat atas —"
                        nilai={form.data.induk_id}
                        onUbah={(v) => form.setData('induk_id', v)}
                        opsi={menu
                            .filter((m) => !m.induk_id && m.lokasi === form.data.lokasi && (edit === 'baru' || m.id !== edit?.id))
                            .map((m) => ({ nilai: m.id, label: m.label }))}
                        galat={form.errors.induk_id}
                    />
                    <BidangPilih
                        id="ikon"
                        label="Ikon"
                        kosong="Tanpa ikon"
                        nilai={form.data.ikon}
                        onUbah={(v) => form.setData('ikon', v)}
                        opsi={ikon.map((i) => ({ nilai: i, label: i }))}
                        galat={form.errors.ikon}
                    />
                    <BidangSakelar id="tab" label="Buka di tab baru" nilai={form.data.buka_tab_baru} onUbah={(v) => form.setData('buka_tab_baru', v)} />
                    <BidangSakelar id="aktif" label="Aktif" nilai={form.data.aktif} onUbah={(v) => form.setData('aktif', v)} />
                    <Button type="submit" className="sm:col-span-2" disabled={form.processing}>
                        Simpan menu
                    </Button>
                </form>
            </DialogForm>
            <DialogForm
                buka={editSosial !== null}
                onTutup={() => setEditSosial(null)}
                judul={editSosial === 'baru' ? 'Tautan sosial baru' : 'Sunting tautan sosial'}
            >
                <form
                    className="grid gap-3"
                    onSubmit={(e) => {
                        e.preventDefault();
                        const opsi = { preserveScroll: true, onSuccess: () => setEditSosial(null) };
                        if (editSosial === 'baru') {
                            fs.post('/admin/tautan-sosial', opsi);
                        } else if (editSosial) {
                            fs.put(`/admin/tautan-sosial/${editSosial.id}`, opsi);
                        }
                    }}
                >
                    <BidangPilih
                        id="platform"
                        label="Platform"
                        nilai={fs.data.nama_platform}
                        onUbah={(v) => fs.setData('nama_platform', v)}
                        opsi={platform.map((p) => ({ nilai: p, label: p }))}
                        galat={fs.errors.nama_platform}
                    />
                    <BidangTeks id="label-sosial" label="Label" wajib nilai={fs.data.label} onUbah={(v) => fs.setData('label', v)} galat={fs.errors.label} />
                    <BidangTeks id="url-sosial" label="URL akun pemilik" nilai={fs.data.url} onUbah={(v) => fs.setData('url', v)} galat={fs.errors.url} />
                    <BidangSakelar id="aktif-sosial" label="Aktif" nilai={fs.data.aktif} onUbah={(v) => fs.setData('aktif', v)} />
                    <Button type="submit" disabled={fs.processing}>
                        Simpan
                    </Button>
                </form>
            </DialogForm>
        </HalamanAdmin>
    );
}
