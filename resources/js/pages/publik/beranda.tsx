import { Link, usePage } from '@inertiajs/react';
import { ArrowRight, BookOpen, Download, Mail, Sparkles } from 'lucide-react';
import { IlustrasiFase } from '@/components/publik/ilustrasi-fase';
import { JudulBagian, KartuAspek, KartuBab, KartuFase } from '@/components/publik/kartu';
import { Muncul } from '@/components/umum/muncul';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { Button } from '@/components/ui/button';
import { cn, formatUkuran } from '@/lib/utils';
import type { Aspek, BabRingkas, Dokumen, Fase, Media, ProfilPenyusun, PropsBersama } from '@/types';

type Bagian = {
    kode: string;
    jenis: string;
    judul: string;
    subjudul: string | null;
    ringkasan: string | null;
    isi: string | null;
    tombol_utama: { teks: string; url: string } | null;
    tombol_pendamping: { teks: string; url: string } | null;
    media: Media | null;
    pengaturan: Record<string, unknown>;
};

type Props = {
    bagian: Bagian[];
    fase: Fase[];
    aspek: Aspek[];
    bab: BabRingkas[];
    faq: { id: number; pertanyaan: string; jawaban: string }[];
    galeri: { id: number; judul: string; ringkasan: string | null; media: Media | null; alt: string }[];
    modul_lengkap: Dokumen | null;
    profil: ProfilPenyusun | null;
};

function TautanTombol({ t, varian = 'default' }: { t: { teks: string; url: string } | null; varian?: 'default' | 'outline' }) {
    if (!t) {
        return null;
    }
    const isi = (
        <>
            {t.teks} <ArrowRight aria-hidden />
        </>
    );

    return (
        <Button asChild size="lg" variant={varian} className="rounded-full px-6">
            {t.url.startsWith('/') ? (
                <Link href={t.url}>{isi}</Link>
            ) : (
                <a href={t.url} rel="noopener noreferrer">
                    {isi}
                </a>
            )}
        </Button>
    );
}

function Hero({ b, p }: { b: Bagian; p: Props }) {
    const angka = [
        { n: 9, l: 'bab materi' },
        { n: 3, l: 'fase sapaan' },
        { n: 4, l: 'aspek karakter' },
        { n: 16, l: 'indikator perilaku' },
    ];

    return (
        <section className="relative overflow-hidden" aria-labelledby="judul-hero">
            <div className="pointer-events-none absolute inset-0 latar-titik opacity-70" aria-hidden />
            <div className="pointer-events-none absolute -top-40 -right-40 size-[32rem] rounded-full bg-[var(--merek-2)] opacity-10 blur-3xl" aria-hidden />
            <div className="relative mx-auto grid max-w-7xl items-center gap-10 px-4 py-14 sm:px-6 md:py-20 lg:grid-cols-[1.1fr_0.9fr]">
                <div>
                    {b.subjudul && (
                        <p className="inline-flex items-center gap-2 rounded-full border bg-card px-3 py-1 text-xs font-bold tracking-wide text-primary uppercase">
                            <Sparkles className="size-3.5" aria-hidden /> {b.subjudul}
                        </p>
                    )}
                    <h1 id="judul-hero" className="mt-5 text-4xl leading-[1.1] font-black tracking-tight text-balance sm:text-5xl lg:text-6xl">
                        {b.judul}
                    </h1>
                    {b.ringkasan && <p className="mt-5 max-w-xl text-lg leading-relaxed text-muted-foreground">{b.ringkasan}</p>}
                    <div className="mt-8 flex flex-wrap gap-3">
                        <TautanTombol t={b.tombol_utama} />
                        <TautanTombol t={b.tombol_pendamping} varian="outline" />
                        {p.modul_lengkap?.url_unduh && (
                            <Button asChild size="lg" variant="ghost" className="rounded-full">
                                <a href={p.modul_lengkap.url_unduh} download>
                                    <Download aria-hidden /> Unduh modul lengkap ({formatUkuran(p.modul_lengkap.ukuran_bita)})
                                </a>
                            </Button>
                        )}
                    </div>
                    {b.pengaturan.tampilkan_angka_struktur !== false && (
                        <dl className="mt-10 grid max-w-xl grid-cols-2 gap-3 sm:grid-cols-4">
                            {angka.map((a) => (
                                <div key={a.l} className="rounded-xl border bg-card/80 px-3 py-3">
                                    <dt className="sr-only">{a.l}</dt>
                                    <dd className="text-2xl font-black text-primary">{a.n}</dd>
                                    <dd className="text-xs text-muted-foreground">{a.l}</dd>
                                </div>
                            ))}
                        </dl>
                    )}
                </div>
                {b.pengaturan.tampilkan_ilustrasi !== false && (
                    <div className="flex justify-center text-foreground">
                        {b.media?.url && b.media.jenis === 'gambar' ? (
                            <img
                                src={b.media.url}
                                alt={b.media.alt}
                                width={b.media.lebar ?? 640}
                                height={b.media.tinggi ?? 520}
                                className="h-auto w-full max-w-lg rounded-3xl"
                                fetchPriority="high"
                            />
                        ) : (
                            <IlustrasiFase label="Ilustrasi alur tiga fase PING: Pre-Class Greeting saat datang, Inter-Class Transition Greeting saat berpindah kegiatan, dan End-Day Closing Greeting saat pulang." />
                        )}
                    </div>
                )}
            </div>
        </section>
    );
}

function Pengantar({ b }: { b: Bagian }) {
    const manfaat = (b.pengaturan.manfaat as string[] | undefined) ?? [];

    return (
        <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6" aria-labelledby="judul-pengantar">
            <div className="grid gap-10 rounded-3xl border bg-card p-6 sm:p-10 lg:grid-cols-2">
                <div>
                    <JudulBagian id="judul-pengantar" kecil={b.subjudul ?? undefined} judul={b.judul} />
                    {b.ringkasan && <p className="-mt-4 leading-relaxed text-foreground/80">{b.ringkasan}</p>}
                </div>
                {manfaat.length > 0 && (
                    <div>
                        <h3 className="mb-4 font-bold">Manfaat panduan bagi guru</h3>
                        <ul className="space-y-3">
                            {manfaat.map((m, i) => (
                                <Muncul as="li" key={m} jeda={i * 0.05} className="flex gap-3 rounded-xl bg-lembut p-4 text-sm">
                                    <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">
                                        {i + 1}
                                    </span>
                                    {m}
                                </Muncul>
                            ))}
                        </ul>
                    </div>
                )}
            </div>
        </section>
    );
}

function BagianDenganTombol({ b, children, id }: { b: Bagian; children: React.ReactNode; id: string }) {
    return (
        <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6" aria-labelledby={id}>
            <div className="flex flex-wrap items-end justify-between gap-4">
                <JudulBagian id={id} kecil={b.subjudul ?? undefined} judul={b.judul} deskripsi={b.ringkasan} />
                {b.tombol_utama && (
                    <Link href={b.tombol_utama.url} className="mb-10 inline-flex items-center gap-1 text-sm font-bold text-primary hover:underline">
                        {b.tombol_utama.teks} <ArrowRight className="size-4" aria-hidden />
                    </Link>
                )}
            </div>
            {children}
        </section>
    );
}

export default function Beranda(props: Props) {
    const { bagian } = props;
    const { situs } = usePage<PropsBersama>().props;

    return (
        <>
            {/* Satu H1 per halaman: bila bagian hero dinonaktifkan, H1 tetap tersedia untuk pembaca layar & SEO. */}
            {!bagian.some((b) => b.jenis === 'hero') && <h1 className="sr-only">{situs.nama_aplikasi}</h1>}
            {bagian.map((b) => {
                switch (b.jenis) {
                    case 'hero':
                        return <Hero key={b.kode} b={b} p={props} />;
                    case 'pengantar':
                        return <Pengantar key={b.kode} b={b} />;
                    case 'fase':
                        return props.fase.length ? (
                            <BagianDenganTombol key={b.kode} b={b} id="judul-fase">
                                <div className="grid gap-5 md:grid-cols-3">
                                    {props.fase.map((f, i) => (
                                        <KartuFase
                                            key={f.id}
                                            fase={f}
                                            jeda={i * 0.08}
                                            tampilkanDurasi={b.pengaturan.tampilkan_durasi !== false}
                                            tampilkanLangkah={b.pengaturan.tampilkan_langkah !== false}
                                        />
                                    ))}
                                </div>
                            </BagianDenganTombol>
                        ) : null;
                    case 'aspek':
                        return props.aspek.length ? (
                            <BagianDenganTombol key={b.kode} b={b} id="judul-aspek">
                                <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                                    {props.aspek.map((a, i) => (
                                        <KartuAspek key={a.id} aspek={a} jeda={i * 0.06} tampilkanIndikator={b.pengaturan.tampilkan_indikator !== false} />
                                    ))}
                                </div>
                            </BagianDenganTombol>
                        ) : null;
                    case 'bab':
                        return props.bab.length ? (
                            <BagianDenganTombol key={b.kode} b={b} id="judul-bab">
                                <div className={cn('grid gap-4', b.pengaturan.tata_letak === 'daftar' ? 'grid-cols-1' : 'sm:grid-cols-2 lg:grid-cols-3')}>
                                    {props.bab.map((x, i) => (
                                        <KartuBab
                                            key={x.id}
                                            bab={x}
                                            jeda={(i % 3) * 0.05}
                                            varian={b.pengaturan.tata_letak === 'daftar' ? 'daftar' : 'grid'}
                                            tampilkanStatusDokumen={b.pengaturan.tampilkan_status_dokumen !== false}
                                        />
                                    ))}
                                </div>
                            </BagianDenganTombol>
                        ) : null;
                    case 'panduan': {
                        const langkah = ((b.pengaturan.langkah as string[] | undefined) ?? []).map((l) => l.split('|').map((s) => s.trim()));

                        return langkah.length ? (
                            <BagianDenganTombol key={b.kode} b={b} id="judul-panduan">
                                <ol className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                    {langkah.map(([judul, isi], i) => (
                                        <Muncul as="li" key={judul} jeda={i * 0.06} className="relative rounded-2xl border bg-card p-6">
                                            <span className="text-5xl font-black text-primary/15" aria-hidden>
                                                0{i + 1}
                                            </span>
                                            <h3 className="mt-2 font-extrabold">{judul}</h3>
                                            {isi && <p className="mt-1 text-sm text-muted-foreground">{isi}</p>}
                                        </Muncul>
                                    ))}
                                </ol>
                            </BagianDenganTombol>
                        ) : null;
                    }
                    case 'profil':
                        return props.profil ? (
                            <section key={b.kode} className="mx-auto max-w-7xl px-4 py-16 sm:px-6" aria-labelledby="judul-profil">
                                <div className="grid items-center gap-8 rounded-3xl border bg-card p-6 sm:p-10 md:grid-cols-[auto_1fr]">
                                    {props.profil.foto?.url && b.pengaturan.tampilkan_foto !== false ? (
                                        <img
                                            src={props.profil.foto.url}
                                            alt={props.profil.foto.alt}
                                            width={160}
                                            height={160}
                                            loading="lazy"
                                            className="size-32 rounded-2xl object-cover sm:size-40"
                                        />
                                    ) : (
                                        <span
                                            className="flex size-32 items-center justify-center rounded-2xl bg-gradient-to-br from-[var(--merek)] to-[var(--merek-2)] text-4xl font-black text-white sm:size-40"
                                            aria-hidden
                                        >
                                            {props.profil.inisial}
                                        </span>
                                    )}
                                    <div>
                                        <p className="text-sm font-bold tracking-widest text-primary uppercase">{b.judul}</p>
                                        <h2 id="judul-profil" className="mt-1 text-2xl font-extrabold">
                                            {props.profil.nama}
                                        </h2>
                                        {(props.profil.jabatan || props.profil.institusi) && (
                                            <p className="text-sm text-muted-foreground">
                                                {[props.profil.jabatan, props.profil.institusi].filter(Boolean).join(' · ')}
                                            </p>
                                        )}
                                        <p className="mt-3 max-w-2xl leading-relaxed text-foreground/80">{props.profil.biografi ?? b.ringkasan}</p>
                                        <Link href="/tentang" className="mt-4 inline-flex items-center gap-1 text-sm font-bold text-primary hover:underline">
                                            Selengkapnya <ArrowRight className="size-4" aria-hidden />
                                        </Link>
                                    </div>
                                </div>
                            </section>
                        ) : b.ringkasan ? (
                            <section key={b.kode} className="mx-auto max-w-7xl px-4 py-10 sm:px-6" aria-labelledby="judul-profil">
                                <div className="rounded-3xl border bg-card p-6 sm:p-8">
                                    <h2 id="judul-profil" className="text-xl font-extrabold">
                                        {b.judul}
                                    </h2>
                                    <p className="mt-2 text-foreground/80">{b.ringkasan}</p>
                                </div>
                            </section>
                        ) : null;
                    case 'galeri':
                        return props.galeri.length ? (
                            <BagianDenganTombol key={b.kode} b={b} id="judul-galeri">
                                <ul className="grid grid-cols-2 gap-3 md:grid-cols-3">
                                    {props.galeri.slice(0, Number(b.pengaturan.jumlah_item ?? 6)).map((g) => (
                                        <li key={g.id} className="overflow-hidden rounded-2xl border bg-card">
                                            {g.media?.url && (
                                                <img
                                                    src={g.media.url}
                                                    alt={g.alt}
                                                    width={g.media.lebar ?? 600}
                                                    height={g.media.tinggi ?? 400}
                                                    loading="lazy"
                                                    className="aspect-[4/3] w-full object-cover"
                                                />
                                            )}
                                            <p className="p-3 text-sm font-semibold">{g.judul}</p>
                                        </li>
                                    ))}
                                </ul>
                            </BagianDenganTombol>
                        ) : null;
                    case 'faq':
                        return props.faq.length ? (
                            <BagianDenganTombol key={b.kode} b={b} id="judul-faq">
                                <Accordion type="single" collapsible className="rounded-2xl border bg-card px-5">
                                    {props.faq.slice(0, Number(b.pengaturan.jumlah_item ?? 5)).map((f) => (
                                        <AccordionItem key={f.id} value={String(f.id)}>
                                            <AccordionTrigger>{f.pertanyaan}</AccordionTrigger>
                                            <AccordionContent className="text-base leading-relaxed text-foreground/80">{f.jawaban}</AccordionContent>
                                        </AccordionItem>
                                    ))}
                                </Accordion>
                            </BagianDenganTombol>
                        ) : null;
                    case 'cta':
                        return (
                            <section key={b.kode} className="mx-auto max-w-7xl px-4 py-16 sm:px-6" aria-labelledby="judul-cta">
                                <div
                                    className={cn(
                                        'relative overflow-hidden rounded-3xl p-8 text-center sm:p-14',
                                        b.pengaturan.gaya === 'polos'
                                            ? 'border bg-lembut'
                                            : 'bg-gradient-to-br from-[var(--merek)] to-[color-mix(in_oklab,var(--merek-2)_80%,black)] text-white',
                                    )}
                                >
                                    <BookOpen className="mx-auto size-10 opacity-80" aria-hidden />
                                    <h2 id="judul-cta" className="mx-auto mt-4 max-w-2xl text-3xl font-black text-balance sm:text-4xl">
                                        {b.judul}
                                    </h2>
                                    {b.ringkasan && <p className="mx-auto mt-3 max-w-xl opacity-90">{b.ringkasan}</p>}
                                    <div className="mt-8 flex flex-wrap justify-center gap-3">
                                        {b.tombol_utama && (
                                            <Button
                                                asChild
                                                size="lg"
                                                variant={b.pengaturan.gaya === 'polos' ? 'default' : 'secondary'}
                                                className="rounded-full px-6"
                                            >
                                                <Link href={b.tombol_utama.url}>
                                                    {b.tombol_utama.teks} <ArrowRight aria-hidden />
                                                </Link>
                                            </Button>
                                        )}
                                        {b.tombol_pendamping && (
                                            <Button
                                                asChild
                                                size="lg"
                                                variant="outline"
                                                className="rounded-full border-white/40 bg-transparent px-6 text-inherit hover:bg-white/10 hover:text-inherit"
                                            >
                                                <Link href={b.tombol_pendamping.url}>{b.tombol_pendamping.teks}</Link>
                                            </Button>
                                        )}
                                    </div>
                                    <p className="mt-6 inline-flex items-center gap-1 text-sm opacity-80">
                                        <Mail className="size-4" aria-hidden /> Ada masukan?{' '}
                                        <Link href="/kontak" className="font-semibold underline">
                                            Hubungi kami
                                        </Link>
                                    </p>
                                </div>
                            </section>
                        );
                    default:
                        return null;
                }
            })}
        </>
    );
}
