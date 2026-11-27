import { router } from '@inertiajs/react';
import { FileText, ImageIcon, Upload, Video } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatUkuran } from '@/lib/utils';
import type { MediaAdmin } from '@/types';

type Jenis = 'gambar' | 'pdf' | 'video_eksternal' | undefined;

function bacaCookie(nama: string): string {
    if (typeof document === 'undefined') {
        return '';
    }
    const cocok = document.cookie.split('; ').find((c) => c.startsWith(`${nama}=`));

    return cocok ? decodeURIComponent(cocok.split('=')[1]) : '';
}

/** Dialog pemilih media dari pustaka (JSON admin) dengan unggah cepat gambar. */
export function PemilihMedia({ buka, onTutup, onPilih, jenis }: { buka: boolean; onTutup: () => void; onPilih: (m: MediaAdmin) => void; jenis?: Jenis }) {
    const [data, setData] = useState<MediaAdmin[]>([]);
    const [q, setQ] = useState('');
    const [memuat, setMemuat] = useState(false);
    const [galat, setGalat] = useState<string | null>(null);
    const [unggah, setUnggah] = useState<{ berkas: File | null; alt: string }>({ berkas: null, alt: '' });

    const muat = useCallback(async () => {
        setMemuat(true);
        try {
            const params = new URLSearchParams({ ...(jenis ? { jenis } : {}), ...(q ? { q } : {}) });
            const r = await fetch(`/admin/media/pilih?${params}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const json = await r.json();
            setData(json.data ?? []);
        } catch {
            setGalat('Daftar media gagal dimuat.');
        } finally {
            setMemuat(false);
        }
    }, [jenis, q]);

    useEffect(() => {
        if (buka) {
            const t = setTimeout(muat, 250);

            return () => clearTimeout(t);
        }
    }, [buka, muat]);

    const kirimUnggah = async () => {
        if (!unggah.berkas) {
            return;
        }
        setGalat(null);
        const fd = new FormData();
        fd.append('jenis_media', jenis === 'pdf' ? 'pdf' : 'gambar');
        fd.append('berkas', unggah.berkas);
        fd.append('teks_alternatif', unggah.alt);
        fd.append('judul', unggah.berkas.name.replace(/\.[^.]+$/, ''));
        const r = await fetch('/admin/media', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-XSRF-TOKEN': bacaCookie('XSRF-TOKEN') },
        });
        const json = await r.json().catch(() => ({}));
        if (!r.ok) {
            const pesan = json.errors ? Object.values(json.errors as Record<string, string[]>)[0]?.[0] : json.message;
            setGalat(pesan ?? 'Unggahan gagal.');

            return;
        }
        setUnggah({ berkas: null, alt: '' });
        onPilih(json.media);
    };

    const Ikon = (m: MediaAdmin) => (m.jenis === 'pdf' ? FileText : m.jenis === 'video_eksternal' ? Video : ImageIcon);

    return (
        <Dialog open={buka} onOpenChange={(v) => !v && onTutup()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Pilih media</DialogTitle>
                    <DialogDescription>Pilih dari pustaka media atau unggah {jenis === 'pdf' ? 'PDF' : 'gambar JPG/PNG/WebP'} baru.</DialogDescription>
                </DialogHeader>
                <div className="space-y-4">
                    <Input type="search" placeholder="Cari judul media…" value={q} onChange={(e) => setQ(e.target.value)} aria-label="Cari media" />
                    {galat && (
                        <p role="alert" className="text-sm text-destructive">
                            {galat}
                        </p>
                    )}
                    <ul className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        {data.map((m) => {
                            const I = Ikon(m);

                            return (
                                <li key={m.id}>
                                    <button
                                        type="button"
                                        onClick={() => onPilih(m)}
                                        className="group w-full overflow-hidden rounded-lg border text-left hover:border-primary focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                                    >
                                        <div className="flex aspect-video items-center justify-center bg-muted">
                                            {m.jenis === 'gambar' && m.url ? (
                                                <img src={m.url} alt="" className="size-full object-cover" loading="lazy" />
                                            ) : (
                                                <I className="size-8 text-muted-foreground" aria-hidden />
                                            )}
                                        </div>
                                        <div className="p-2">
                                            <p className="truncate text-sm font-medium">{m.judul}</p>
                                            <p className="text-xs text-muted-foreground">
                                                {m.jenis === 'video_eksternal' ? 'Video eksternal' : formatUkuran(m.ukuran_bita)}
                                            </p>
                                        </div>
                                    </button>
                                </li>
                            );
                        })}
                    </ul>
                    {!memuat && !data.length && <p className="text-sm text-muted-foreground">Belum ada media yang cocok.</p>}
                    {jenis !== 'video_eksternal' && (
                        <div className="space-y-3 rounded-lg border border-dashed p-4">
                            <p className="flex items-center gap-2 text-sm font-semibold">
                                <Upload className="size-4" aria-hidden /> Unggah cepat
                            </p>
                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="grid gap-1.5">
                                    <Label htmlFor="unggah-cepat-berkas">Berkas</Label>
                                    <Input
                                        id="unggah-cepat-berkas"
                                        type="file"
                                        accept={jenis === 'pdf' ? 'application/pdf' : 'image/jpeg,image/png,image/webp'}
                                        onChange={(e) => setUnggah((u) => ({ ...u, berkas: e.target.files?.[0] ?? null }))}
                                    />
                                </div>
                                {jenis !== 'pdf' && (
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="unggah-cepat-alt">Teks alternatif</Label>
                                        <Input
                                            id="unggah-cepat-alt"
                                            value={unggah.alt}
                                            onChange={(e) => setUnggah((u) => ({ ...u, alt: e.target.value }))}
                                            placeholder="Deskripsikan isi gambar"
                                        />
                                    </div>
                                )}
                            </div>
                            <Button type="button" size="sm" onClick={kirimUnggah} disabled={!unggah.berkas || (jenis !== 'pdf' && !unggah.alt)}>
                                Unggah & pilih
                            </Button>
                        </div>
                    )}
                    <p className="text-xs text-muted-foreground">
                        Kelola semua berkas di{' '}
                        <button type="button" className="underline" onClick={() => router.visit('/admin/media')}>
                            Pustaka Media
                        </button>
                        .
                    </p>
                </div>
            </DialogContent>
        </Dialog>
    );
}

/** Bidang formulir untuk memilih satu media (menyimpan ID). */
export function BidangMedia({
    id,
    label,
    media,
    onUbah,
    jenis = 'gambar',
    galat,
    petunjuk,
}: {
    id: string;
    label: string;
    media: { id: number; url: string | null; judul: string } | null;
    onUbah: (m: { id: number; url: string | null; judul: string } | null) => void;
    jenis?: Jenis;
    galat?: string;
    petunjuk?: string;
}) {
    const [buka, setBuka] = useState(false);

    return (
        <div className="grid gap-1.5">
            <Label htmlFor={id}>{label}</Label>
            <div className="flex items-center gap-3 rounded-lg border p-2">
                <div className="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-md bg-muted">
                    {media?.url && jenis === 'gambar' ? (
                        <img src={media.url} alt="" className="size-full object-cover" />
                    ) : (
                        <ImageIcon className="size-5 text-muted-foreground" aria-hidden />
                    )}
                </div>
                <p className="min-w-0 flex-1 truncate text-sm">{media ? media.judul : <span className="text-muted-foreground">Belum dipilih</span>}</p>
                <Button id={id} type="button" variant="outline" size="sm" onClick={() => setBuka(true)}>
                    Pilih
                </Button>
                {media && (
                    <Button type="button" variant="ghost" size="sm" onClick={() => onUbah(null)}>
                        Lepas
                    </Button>
                )}
            </div>
            {petunjuk && <p className="text-xs text-muted-foreground">{petunjuk}</p>}
            {galat && <p className="text-sm text-destructive">{galat}</p>}
            <PemilihMedia
                buka={buka}
                jenis={jenis}
                onTutup={() => setBuka(false)}
                onPilih={(m) => {
                    onUbah({ id: m.id, url: m.url, judul: m.judul });
                    setBuka(false);
                }}
            />
        </div>
    );
}
