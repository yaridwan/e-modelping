import { Globe, Mail } from 'lucide-react';
import { JudulBagian, KartuAspek, KartuFase } from '@/components/publik/kartu';
import { KepalaHalaman } from '@/components/publik/kepala-halaman';
import { HtmlAman } from '@/components/umum/html-aman';
import type { Aspek, Fase, ProfilPenyusun } from '@/types';

type Referensi = {
    id: number;
    penulis: string;
    tahun: number | null;
    judul: string;
    penerbit: string | null;
    volume: string | null;
    nomor: string | null;
    halaman: string | null;
    doi: string | null;
    url: string | null;
};

export default function Tentang({
    halaman,
    fase,
    aspek,
    profil,
    referensi,
}: {
    halaman: { judul: string; ringkasan: string | null; isi: string | null };
    fase: Fase[];
    aspek: Aspek[];
    profil: ProfilPenyusun | null;
    referensi: Referensi[];
}) {
    return (
        <>
            <KepalaHalaman jejak={[{ nama: 'Beranda', url: '/' }, { nama: 'Tentang' }]} judul={halaman.judul} deskripsi={halaman.ringkasan} />
            <div className="mx-auto max-w-7xl space-y-16 px-4 py-12 sm:px-6">
                <HtmlAman html={halaman.isi} className="max-w-3xl" />

                <section id="fase" aria-labelledby="judul-fase" className="scroll-mt-24">
                    <JudulBagian id="judul-fase" kecil="Sintaks model" judul="Tiga fase PING" />
                    <div className="grid gap-5 md:grid-cols-3">
                        {fase.map((f, i) => (
                            <KartuFase key={f.id} fase={f} jeda={i * 0.06} />
                        ))}
                    </div>
                </section>

                <section aria-labelledby="judul-aspek">
                    <JudulBagian id="judul-aspek" kecil="Karakter yang dibiasakan" judul="Empat aspek karakter" />
                    <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        {aspek.map((a, i) => (
                            <KartuAspek key={a.id} aspek={a} jeda={i * 0.05} tampilkanIndikator={false} />
                        ))}
                    </div>
                </section>

                {profil && (
                    <section aria-labelledby="judul-penyusun" className="rounded-3xl border bg-card p-6 sm:p-10">
                        <div className="grid gap-6 md:grid-cols-[auto_1fr]">
                            {profil.foto?.url ? (
                                <img
                                    src={profil.foto.url}
                                    alt={profil.foto.alt}
                                    width={176}
                                    height={176}
                                    loading="lazy"
                                    className="size-36 rounded-2xl object-cover sm:size-44"
                                />
                            ) : (
                                <span
                                    className="flex size-36 items-center justify-center rounded-2xl bg-gradient-to-br from-[var(--merek)] to-[var(--merek-2)] text-5xl font-black text-white sm:size-44"
                                    aria-hidden
                                >
                                    {profil.inisial}
                                </span>
                            )}
                            <div>
                                <p className="text-sm font-bold tracking-widest text-primary uppercase">Profil penyusun</p>
                                <h2 id="judul-penyusun" className="mt-1 text-2xl font-extrabold">
                                    {profil.nama}
                                </h2>
                                {(profil.jabatan || profil.institusi) && (
                                    <p className="text-muted-foreground">{[profil.jabatan, profil.institusi].filter(Boolean).join(' · ')}</p>
                                )}
                                {profil.bidang_keahlian && <p className="mt-2 text-sm">Bidang keahlian: {profil.bidang_keahlian}</p>}
                                {profil.biografi && <p className="mt-4 max-w-3xl leading-relaxed whitespace-pre-line text-foreground/85">{profil.biografi}</p>}
                                <div className="mt-4 flex flex-wrap gap-4 text-sm">
                                    {profil.situs && (
                                        <a
                                            href={profil.situs}
                                            rel="noopener noreferrer me"
                                            target="_blank"
                                            className="inline-flex items-center gap-1 text-primary underline"
                                        >
                                            <Globe className="size-4" aria-hidden /> Situs pribadi
                                        </a>
                                    )}
                                    {profil.surel_publik && (
                                        <a href={`mailto:${profil.surel_publik}`} className="inline-flex items-center gap-1 text-primary underline">
                                            <Mail className="size-4" aria-hidden /> {profil.surel_publik}
                                        </a>
                                    )}
                                </div>
                            </div>
                        </div>
                    </section>
                )}

                {referensi.length > 0 && (
                    <section aria-labelledby="judul-pustaka">
                        <JudulBagian
                            id="judul-pustaka"
                            kecil="Rujukan"
                            judul="Daftar pustaka"
                            deskripsi="Sebagaimana tercantum pada dokumen modul. DOI/URL ditampilkan hanya bila tertulis pada sumber."
                        />
                        <ol className="max-w-4xl space-y-3 text-sm leading-relaxed">
                            {referensi.map((r) => (
                                <li key={r.id} className="pl-6 -indent-6">
                                    {r.penulis} ({r.tahun ?? 't.t.'}). <em>{r.judul}</em>
                                    {r.penerbit && `. ${r.penerbit}`}
                                    {r.volume && `, ${r.volume}`}
                                    {r.nomor && `(${r.nomor})`}
                                    {r.halaman && `, ${r.halaman}`}.{' '}
                                    {(r.doi || r.url) && (
                                        <a
                                            href={r.doi ? `https://doi.org/${r.doi}` : (r.url ?? '#')}
                                            rel="noopener noreferrer"
                                            target="_blank"
                                            className="break-all text-primary underline"
                                        >
                                            {r.doi ? `https://doi.org/${r.doi}` : r.url}
                                        </a>
                                    )}
                                </li>
                            ))}
                        </ol>
                    </section>
                )}
            </div>
        </>
    );
}
