import { Link } from '@inertiajs/react';
import { AlertCircle, ArrowLeft, ArrowRight, CalendarDays, CheckCircle2, Clock, Lightbulb, ListChecks, MessageSquareQuote, Target } from 'lucide-react';
import { useCallback } from 'react';
import {
    DaftarIsi,
    LEBAR_BACA,
    PengaturanBaca,
    TombolBagikan,
    TombolHapusRiwayat,
    TombolPenanda,
    usePenandaBaca,
    usePreferensiBaca,
} from '@/components/publik/alat-baca';
import { KepalaHalaman } from '@/components/publik/kepala-halaman';
import { PanelDokumen } from '@/components/publik/panel-dokumen';
import { HtmlAman } from '@/components/umum/html-aman';
import { formatTanggal } from '@/lib/utils';
import type { Aspek, Dokumen, Fase, Media } from '@/types';

type Subbab = {
    id: number;
    kode: string;
    judul: string;
    slug: string;
    ringkasan: string | null;
    isi: string;
    contoh_praktik: string | null;
    media: Media | null;
};

type Props = {
    bab: {
        id: number;
        nomor: number;
        romawi: string;
        judul: string;
        slug: string;
        ringkasan: string | null;
        pengantar: string | null;
        tujuan_pembelajaran: string[];
        poin_penting: string[];
        sampul: Media | null;
        waktu_baca_menit: number;
        jumlah_kata: number;
        diperbarui_pada: string | null;
        subbab: Subbab[];
        fase: Fase[];
    };
    aspek: Aspek[];
    dokumen: Dokumen | null;
    sebelumnya: { romawi: string; judul: string; url: string } | null;
    berikutnya: { romawi: string; judul: string; url: string } | null;
};

const NAMA_FASE: Record<string, string> = { PCG: 'Fase I', ICTG: 'Fase II', EDCG: 'Fase III' };

function MediaPendukung({ media }: { media: Media }) {
    if (media.jenis === 'gambar' && media.url) {
        return (
            <figure>
                <img src={media.url} alt={media.alt} width={media.lebar ?? 800} height={media.tinggi ?? 500} loading="lazy" className="rounded-xl border" />
                {media.keterangan && <figcaption className="mt-2 text-sm text-muted-foreground">{media.keterangan}</figcaption>}
            </figure>
        );
    }
    if (media.jenis === 'video_eksternal' && media.url) {
        return <VideoTertunda url={media.url} judul={media.judul} tautan={media.tautan_asli ?? null} />;
    }

    return null;
}

/** Video pihak ketiga baru dimuat setelah interaksi pembaca. */
function VideoTertunda({ url, judul, tautan }: { url: string; judul: string; tautan: string | null }) {
    return (
        <details className="group rounded-xl border bg-muted/40">
            <summary className="cursor-pointer list-none p-4 font-semibold">
                ▶ Putar video: {judul} <span className="text-sm font-normal text-muted-foreground">(dimuat dari penyedia video setelah diklik)</span>
            </summary>
            <div className="aspect-video">
                <iframe
                    src={url}
                    title={judul}
                    className="size-full rounded-b-xl"
                    loading="lazy"
                    allow="accelerometer; encrypted-media; picture-in-picture"
                    allowFullScreen
                    referrerPolicy="strict-origin-when-cross-origin"
                    sandbox="allow-scripts allow-same-origin allow-presentation"
                />
            </div>
            {tautan && (
                <a href={tautan} rel="noopener noreferrer" target="_blank" className="block p-3 text-sm text-primary underline">
                    Buka di situs penyedia
                </a>
            )}
        </details>
    );
}

function DetailFase({ fase }: { fase: Fase }) {
    return (
        <section id={`fase-${fase.slug}`} aria-labelledby={`judul-fase-${fase.slug}`} className="scroll-mt-24 rounded-2xl border bg-card p-5 sm:p-6">
            <p className="text-xs font-bold tracking-widest text-primary uppercase">{NAMA_FASE[fase.kode]}</p>
            <h3 id={`judul-fase-${fase.slug}`} className="text-xl font-extrabold">
                {fase.nama} <span className="font-semibold text-muted-foreground">· {fase.durasi}</span>
            </h3>
            <ol className="mt-4 space-y-3">
                {fase.langkah?.map((l) => (
                    <li key={l.id} className="rounded-xl bg-muted/50 p-4">
                        <p className="font-bold">
                            Langkah {l.nomor}: {l.judul}
                        </p>
                        <p className="mt-1 text-sm text-foreground/80">{l.penjelasan}</p>
                        {l.contoh_dialog.length > 0 && (
                            <ul className="mt-2 space-y-1 text-sm">
                                {l.contoh_dialog.map((d, i) => (
                                    <li key={i} className="flex gap-2">
                                        <MessageSquareQuote className="mt-0.5 size-4 shrink-0 text-primary" aria-hidden />
                                        <span>
                                            <strong>{d.pembicara}:</strong> “{d.ucapan}”
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </li>
                ))}
            </ol>
            <Link href={fase.url} className="mt-4 inline-flex items-center gap-1 text-sm font-bold text-primary hover:underline">
                Halaman lengkap {fase.nama} <ArrowRight className="size-4" aria-hidden />
            </Link>
        </section>
    );
}

function TabelIndikator({ aspek }: { aspek: Aspek[] }) {
    return (
        <div className="materi">
            <table>
                <caption>Ringkasan 16 indikator karakter (data referensi)</caption>
                <thead>
                    <tr>
                        <th scope="col">No</th>
                        <th scope="col">Aspek</th>
                        <th scope="col">Indikator perilaku</th>
                        <th scope="col">Fase pengamatan</th>
                    </tr>
                </thead>
                <tbody>
                    {aspek.flatMap((a) =>
                        a.indikator.map((i) => (
                            <tr key={i.nomor}>
                                <td>{i.nomor}</td>
                                <td>{a.nama}</td>
                                <td>{i.rumusan}</td>
                                <td>{i.fase_pengamatan.length === 3 ? 'Seluruh fase' : i.fase_pengamatan.map((f) => NAMA_FASE[f]).join(', ')}</td>
                            </tr>
                        )),
                    )}
                </tbody>
            </table>
        </div>
    );
}

export default function DetailBab({ bab, aspek, dokumen, sebelumnya, berikutnya }: Props) {
    const { pref, ubah } = usePreferensiBaca();
    const penanda = usePenandaBaca(bab.slug, `Bab ${bab.romawi} ${bab.judul}`);
    const daftar = [
        ...(bab.tujuan_pembelajaran.length ? [{ id: 'tujuan-belajar', label: 'Tujuan belajar' }] : []),
        ...bab.subbab.map((s) => ({ id: s.slug, label: s.judul, kode: s.kode.split('-')[1] })),
        ...(bab.fase.length ? [{ id: 'fase-pada-bab', label: 'Langkah tiap fase' }] : []),
        ...(aspek.length ? [{ id: 'ringkasan-indikator', label: 'Ringkasan 16 indikator' }] : []),
        ...(bab.poin_penting.length ? [{ id: 'perlu-diperhatikan', label: 'Hal yang perlu diperhatikan' }] : []),
        { id: 'dokumen-bab', label: 'Dokumen PDF' },
    ];
    const subbabTerakhir = bab.subbab.find((s) => s.slug === penanda.posisiTerakhir);
    const catatPosisi = useCallback((id: string) => penanda.catatPosisi(id), [penanda]);

    return (
        <>
            <KepalaHalaman
                jejak={[{ nama: 'Beranda', url: '/' }, { nama: 'Modul', url: '/modul' }, { nama: `Bab ${bab.romawi}` }]}
                kecil={`Bab ${bab.romawi}`}
                judul={bab.judul}
                deskripsi={bab.ringkasan}
            >
                <div className="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-muted-foreground">
                    <span className="inline-flex items-center gap-1.5">
                        <Clock className="size-4" aria-hidden /> ±{bab.waktu_baca_menit} menit baca
                    </span>
                    <span className="inline-flex items-center gap-1.5">
                        <ListChecks className="size-4" aria-hidden /> {bab.subbab.length} subbab
                    </span>
                    {bab.diperbarui_pada && (
                        <span className="inline-flex items-center gap-1.5">
                            <CalendarDays className="size-4" aria-hidden /> Diperbarui {formatTanggal(bab.diperbarui_pada)}
                        </span>
                    )}
                </div>
                <div className="tidak-dicetak mt-5 flex flex-wrap gap-2">
                    <PengaturanBaca pref={pref} ubah={ubah} />
                    <TombolPenanda ditandai={penanda.ditandai} alihkan={penanda.alihkan} />
                    <TombolBagikan judul={`Bab ${bab.romawi}: ${bab.judul}`} />
                </div>
                {subbabTerakhir && (
                    <p className="mt-3 text-sm">
                        <a href={`#${subbabTerakhir.slug}`} className="font-semibold text-primary underline">
                            Lanjutkan dari “{subbabTerakhir.judul}”
                        </a>
                    </p>
                )}
            </KepalaHalaman>

            <div className="mx-auto grid max-w-7xl gap-10 px-4 py-10 sm:px-6 lg:grid-cols-[15rem_1fr]">
                <aside className="tidak-dicetak">
                    <DaftarIsi item={daftar} onAktif={catatPosisi} />
                </aside>

                <article className="min-w-0" style={{ maxWidth: LEBAR_BACA[pref.lebar] }}>
                    {bab.sampul?.url && (
                        <img
                            src={bab.sampul.url}
                            alt={bab.sampul.alt}
                            width={bab.sampul.lebar ?? 1200}
                            height={bab.sampul.tinggi ?? 600}
                            className="mb-8 w-full rounded-2xl border"
                            fetchPriority="high"
                        />
                    )}

                    {bab.tujuan_pembelajaran.length > 0 && (
                        <section
                            id="tujuan-belajar"
                            aria-labelledby="judul-tujuan"
                            className="mb-10 scroll-mt-24 rounded-2xl border border-primary/20 bg-lembut p-6"
                        >
                            <h2 id="judul-tujuan" className="flex items-center gap-2 text-lg font-extrabold">
                                <Target className="size-5 text-primary" aria-hidden /> Tujuan belajar
                            </h2>
                            <ul className="mt-3 space-y-2">
                                {bab.tujuan_pembelajaran.map((t) => (
                                    <li key={t} className="flex gap-2 text-[0.97rem]">
                                        <CheckCircle2 className="mt-1 size-4 shrink-0 text-primary" aria-hidden /> {t}
                                    </li>
                                ))}
                            </ul>
                        </section>
                    )}

                    <div style={{ ['--ukuran-baca' as string]: `${pref.ukuran}px` }}>
                        <HtmlAman html={bab.pengantar} className="mb-10" />

                        {bab.subbab.map((s) => (
                            <section key={s.id} id={s.slug} aria-labelledby={`judul-${s.slug}`} className="mb-12 scroll-mt-24">
                                <p className="font-mono text-xs font-bold tracking-widest text-primary">{s.kode}</p>
                                <h2 id={`judul-${s.slug}`} className="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">
                                    {s.judul}
                                </h2>
                                <HtmlAman html={s.isi} className="mt-5" />
                                {s.media && (
                                    <div className="mt-6">
                                        <MediaPendukung media={s.media} />
                                    </div>
                                )}
                                {s.contoh_praktik && (
                                    <aside className="mt-6 rounded-2xl border bg-card p-5" aria-label="Contoh praktik">
                                        <p className="mb-2 flex items-center gap-2 font-bold">
                                            <Lightbulb className="size-5 text-amber-500" aria-hidden /> Contoh praktik
                                        </p>
                                        <HtmlAman html={s.contoh_praktik} />
                                    </aside>
                                )}
                            </section>
                        ))}
                    </div>

                    {bab.fase.length > 0 && (
                        <section id="fase-pada-bab" aria-labelledby="judul-fase-bab" className="mb-12 scroll-mt-24 space-y-5">
                            <h2 id="judul-fase-bab" className="text-2xl font-extrabold">
                                Langkah tiap fase (ringkasan terstruktur)
                            </h2>
                            {bab.fase.map((f) => (
                                <DetailFase key={f.id} fase={f} />
                            ))}
                        </section>
                    )}

                    {aspek.length > 0 && (
                        <section id="ringkasan-indikator" aria-labelledby="judul-indikator" className="mb-12 scroll-mt-24">
                            <h2 id="judul-indikator" className="mb-3 text-2xl font-extrabold">
                                Ringkasan 16 indikator
                            </h2>
                            {bab.nomor === 7 && (
                                <div className="mb-4 rounded-xl border bg-card p-4 text-sm leading-relaxed">
                                    <p>
                                        Skala: <strong>BB = 1</strong>, <strong>MB = 2</strong>, <strong>BSH = 3</strong>, <strong>BSB = 4</strong>. Jumlah skor
                                        16 indikator berkisar <strong>16–64</strong>; persentase = skor ÷ 64 × 100%.
                                    </p>
                                    <p className="mt-2 flex gap-2 text-muted-foreground">
                                        <AlertCircle className="mt-0.5 size-4 shrink-0 text-amber-600" aria-hidden /> Perilaku yang belum memperoleh kesempatan
                                        diamati tidak otomatis diberi BB. Kosongkan skor dan tulis “belum ada kesempatan pengamatan”. Materi ini adalah panduan;
                                        website tidak menyimpan nilai anak.
                                    </p>
                                </div>
                            )}
                            <TabelIndikator aspek={aspek} />
                            <Link href="/karakter" className="mt-3 inline-flex items-center gap-1 text-sm font-bold text-primary hover:underline">
                                Contoh pengamatan & rubrik lengkap <ArrowRight className="size-4" aria-hidden />
                            </Link>
                        </section>
                    )}

                    {bab.poin_penting.length > 0 && (
                        <section id="perlu-diperhatikan" aria-labelledby="judul-poin" className="mb-12 scroll-mt-24 rounded-2xl border bg-card p-6">
                            <h2 id="judul-poin" className="flex items-center gap-2 text-lg font-extrabold">
                                <Lightbulb className="size-5 text-amber-500" aria-hidden /> Ringkasan & hal yang perlu diperhatikan
                            </h2>
                            <ul className="mt-3 list-disc space-y-2 pl-5">
                                {bab.poin_penting.map((p) => (
                                    <li key={p}>{p}</li>
                                ))}
                            </ul>
                        </section>
                    )}

                    <div id="dokumen-bab" className="mb-12 scroll-mt-24">
                        <PanelDokumen dokumen={dokumen} judulBab={`Bab ${bab.romawi} ${bab.judul}`} />
                    </div>

                    <nav aria-label="Navigasi antarbab" className="tidak-dicetak grid gap-3 sm:grid-cols-2">
                        {sebelumnya ? (
                            <Link href={sebelumnya.url} className="group rounded-2xl border bg-card p-4 hover:border-primary">
                                <span className="flex items-center gap-1 text-xs text-muted-foreground">
                                    <ArrowLeft className="size-3.5" aria-hidden /> Bab sebelumnya
                                </span>
                                <span className="mt-1 block font-bold group-hover:text-primary">
                                    Bab {sebelumnya.romawi} · {sebelumnya.judul}
                                </span>
                            </Link>
                        ) : (
                            <span />
                        )}
                        {berikutnya && (
                            <Link href={berikutnya.url} className="group rounded-2xl border bg-card p-4 text-right hover:border-primary">
                                <span className="flex items-center justify-end gap-1 text-xs text-muted-foreground">
                                    Bab berikutnya <ArrowRight className="size-3.5" aria-hidden />
                                </span>
                                <span className="mt-1 block font-bold group-hover:text-primary">
                                    Bab {berikutnya.romawi} · {berikutnya.judul}
                                </span>
                            </Link>
                        )}
                    </nav>

                    <p className="tidak-dicetak mt-8 text-xs text-muted-foreground">
                        Preferensi baca dan penanda tersimpan di perangkat ini saja. <TombolHapusRiwayat onHapus={penanda.hapusSemua} />
                    </p>
                </article>
            </div>
        </>
    );
}
