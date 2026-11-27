import { Link } from '@inertiajs/react';
import { LencanaStatus } from '@/components/admin/daftar';
import { HalamanAdmin } from '@/components/admin/halaman-admin';
import { HtmlAman } from '@/components/umum/html-aman';
import { Button } from '@/components/ui/button';

type Props = {
    bab: {
        id: number;
        romawi: string;
        judul: string;
        ringkasan: string | null;
        pengantar: string | null;
        status: string;
        tujuan_pembelajaran: string[];
        poin_penting: string[];
        subbab: {
            id: number;
            kode: string;
            judul: string;
            slug: string;
            status: string;
            ringkasan: string | null;
            isi: string;
            contoh_praktik: string | null;
        }[];
    };
};

/** Pratinjau admin (noindex, no-store): menampilkan juga subbab draf/arsip dengan label status. */
export default function PratinjauBab({ bab }: Props) {
    return (
        <HalamanAdmin
            judul={`Pratinjau Bab ${bab.romawi}`}
            jejak={[{ judul: 'Bab & Subbab', href: '/admin/bab' }, { judul: `Bab ${bab.romawi}`, href: `/admin/bab/${bab.id}/edit` }, { judul: 'Pratinjau' }]}
            deskripsi="Pratinjau ini hanya untuk admin. Bagian berlabel draf/arsip tidak tampil di website publik."
            aksi={
                <Button asChild variant="outline" size="sm">
                    <Link href={`/admin/bab/${bab.id}/edit`}>Kembali menyunting</Link>
                </Button>
            }
        >
            <article className="mx-auto max-w-3xl rounded-2xl border bg-card p-6 sm:p-10">
                <p className="text-sm font-bold text-primary">
                    Bab {bab.romawi} · <LencanaStatus status={bab.status} />
                </p>
                <h2 className="mt-2 text-3xl font-black">{bab.judul}</h2>
                {bab.ringkasan && <p className="mt-3 text-lg text-muted-foreground">{bab.ringkasan}</p>}
                {bab.tujuan_pembelajaran.length > 0 && (
                    <ul className="mt-6 list-disc space-y-1 rounded-xl bg-lembut p-5 pl-10">
                        {bab.tujuan_pembelajaran.map((t) => (
                            <li key={t}>{t}</li>
                        ))}
                    </ul>
                )}
                <HtmlAman html={bab.pengantar} className="mt-6" />
                {bab.subbab.map((s) => (
                    <section key={s.id} className={s.status !== 'terbit' ? 'mt-10 rounded-xl border-2 border-dashed border-amber-400 p-4' : 'mt-10'}>
                        <p className="flex items-center gap-2 font-mono text-xs text-primary">
                            {s.kode} <LencanaStatus status={s.status} />
                        </p>
                        <h3 className="mt-1 text-2xl font-extrabold">{s.judul}</h3>
                        <HtmlAman html={s.isi} className="mt-4" />
                        <HtmlAman html={s.contoh_praktik} className="mt-4 rounded-xl border p-4" />
                    </section>
                ))}
            </article>
        </HalamanAdmin>
    );
}
