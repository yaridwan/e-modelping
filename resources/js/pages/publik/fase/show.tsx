import { Link } from '@inertiajs/react';
import { BookOpen, CheckCircle2, Clock, MessageSquareQuote, Repeat, Target, UserRound, Users } from 'lucide-react';
import { KepalaHalaman } from '@/components/publik/kepala-halaman';
import { cn } from '@/lib/utils';
import type { Fase } from '@/types';

export default function HalamanFase({
    fase,
    semua_fase,
}: {
    fase: Fase;
    semua_fase: { nama: string; kode: string; judul_indonesia: string; url: string; aktif: boolean }[];
}) {
    return (
        <>
            <KepalaHalaman
                jejak={[{ nama: 'Beranda', url: '/' }, { nama: 'Fase PING', url: '/tentang' }, { nama: fase.nama }]}
                kecil={fase.judul_indonesia}
                judul={fase.nama}
                deskripsi={fase.deskripsi}
            >
                <dl className="mt-6 flex flex-wrap gap-3 text-sm">
                    <div className="inline-flex items-center gap-1.5 rounded-full bg-secondary px-3 py-1.5 font-semibold text-secondary-foreground">
                        <Clock className="size-4" aria-hidden />
                        <dt className="sr-only">Durasi panduan</dt>
                        <dd>{fase.durasi}</dd>
                    </div>
                    {fase.frekuensi && (
                        <div className="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5">
                            <Repeat className="size-4" aria-hidden />
                            <dt className="sr-only">Frekuensi</dt>
                            <dd>{fase.frekuensi}</dd>
                        </div>
                    )}
                </dl>
            </KepalaHalaman>
            <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6">
                <nav aria-label="Pilih fase" className="mb-10 grid gap-2 sm:grid-cols-3">
                    {semua_fase.map((f, i) => (
                        <Link
                            key={f.kode}
                            href={f.url}
                            aria-current={f.aktif ? 'page' : undefined}
                            className={cn('rounded-xl border p-4 transition-colors', f.aktif ? 'border-primary bg-lembut' : 'bg-card hover:border-primary/50')}
                        >
                            <span className="text-xs font-bold text-primary">Fase {['I', 'II', 'III'][i]}</span>
                            <span className="block font-bold">{f.nama}</span>
                            <span className="block text-xs text-muted-foreground">{f.judul_indonesia}</span>
                        </Link>
                    ))}
                </nav>

                <div className="grid gap-10 lg:grid-cols-[1fr_20rem]">
                    <div className="min-w-0 space-y-10">
                        <section aria-labelledby="judul-langkah">
                            <h2 id="judul-langkah" className="text-2xl font-extrabold">
                                Langkah kegiatan
                            </h2>
                            <ol className="mt-5 space-y-4">
                                {fase.langkah?.map((l) => (
                                    <li key={l.id} className="relative rounded-2xl border bg-card p-5 pl-16">
                                        <span className="absolute top-5 left-5 flex size-8 items-center justify-center rounded-full bg-primary text-sm font-black text-primary-foreground">
                                            {l.nomor}
                                        </span>
                                        <h3 className="text-lg font-bold">{l.judul}</h3>
                                        <p className="mt-1 leading-relaxed text-foreground/80">{l.penjelasan}</p>
                                        <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                            {l.aktivitas_guru && (
                                                <p className="rounded-lg bg-muted/60 p-3 text-sm">
                                                    <span className="mb-1 flex items-center gap-1 font-semibold">
                                                        <UserRound className="size-4" aria-hidden /> Guru
                                                    </span>
                                                    {l.aktivitas_guru}
                                                </p>
                                            )}
                                            {l.aktivitas_anak && (
                                                <p className="rounded-lg bg-muted/60 p-3 text-sm">
                                                    <span className="mb-1 flex items-center gap-1 font-semibold">
                                                        <Users className="size-4" aria-hidden /> Anak
                                                    </span>
                                                    {l.aktivitas_anak}
                                                </p>
                                            )}
                                        </div>
                                        {l.contoh_dialog.length > 0 && (
                                            <figure className="mt-3 rounded-lg border-l-4 border-primary/60 bg-lembut p-3">
                                                <figcaption className="mb-1 text-xs font-bold text-muted-foreground uppercase">Contoh dialog</figcaption>
                                                <ul className="space-y-1 text-sm">
                                                    {l.contoh_dialog.map((d, i) => (
                                                        <li key={i} className="flex gap-2">
                                                            <MessageSquareQuote className="mt-0.5 size-4 shrink-0 text-primary" aria-hidden />
                                                            <span>
                                                                <strong>{d.pembicara}:</strong> “{d.ucapan}”
                                                            </span>
                                                        </li>
                                                    ))}
                                                </ul>
                                            </figure>
                                        )}
                                    </li>
                                ))}
                            </ol>
                        </section>
                        {!!fase.penguatan_karakter?.length && (
                            <section aria-labelledby="judul-penguatan">
                                <h2 id="judul-penguatan" className="text-2xl font-extrabold">
                                    Penguatan karakter
                                </h2>
                                <div className="materi mt-4">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th scope="col">Aspek karakter</th>
                                                <th scope="col">Bentuk pembiasaan/penguatan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {fase.penguatan_karakter.map((p) => (
                                                <tr key={p.aspek}>
                                                    <td>{p.aspek}</td>
                                                    <td>{p.bentuk}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        )}
                    </div>
                    <aside className="space-y-5">
                        {!!fase.tujuan?.length && (
                            <section className="rounded-2xl border bg-card p-5" aria-labelledby="judul-tujuan-fase">
                                <h2 id="judul-tujuan-fase" className="flex items-center gap-2 font-bold">
                                    <Target className="size-4 text-primary" aria-hidden /> Tujuan fase
                                </h2>
                                <ul className="mt-3 space-y-2 text-sm">
                                    {fase.tujuan.map((t) => (
                                        <li key={t} className="flex gap-2">
                                            <CheckCircle2 className="mt-0.5 size-4 shrink-0 text-primary" aria-hidden /> {t}
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        )}
                        {fase.waktu_pelaksanaan && (
                            <section className="rounded-2xl border bg-card p-5" aria-labelledby="judul-waktu">
                                <h2 id="judul-waktu" className="font-bold">
                                    Waktu pelaksanaan
                                </h2>
                                <p className="mt-2 text-sm text-foreground/80">{fase.waktu_pelaksanaan}</p>
                            </section>
                        )}
                        {!!fase.indikator_keberhasilan?.length && (
                            <section className="rounded-2xl border bg-card p-5" aria-labelledby="judul-keberhasilan">
                                <h2 id="judul-keberhasilan" className="font-bold">
                                    Indikator keberhasilan fase
                                </h2>
                                <ul className="mt-3 list-disc space-y-1.5 pl-5 text-sm">
                                    {fase.indikator_keberhasilan.map((t) => (
                                        <li key={t}>{t}</li>
                                    ))}
                                </ul>
                            </section>
                        )}
                        {fase.catatan_guru && (
                            <section
                                className="rounded-2xl border border-amber-300/60 bg-amber-50 p-5 dark:border-amber-900 dark:bg-amber-950/30"
                                aria-labelledby="judul-catatan"
                            >
                                <h2 id="judul-catatan" className="font-bold">
                                    Catatan guru
                                </h2>
                                <p className="mt-2 text-sm">{fase.catatan_guru}</p>
                            </section>
                        )}
                        {fase.bab && (
                            <Link href={fase.bab.url} className="flex items-center gap-2 rounded-2xl border bg-card p-5 font-semibold hover:border-primary">
                                <BookOpen className="size-5 text-primary" aria-hidden /> Baca Bab {fase.bab.romawi}: {fase.bab.judul}
                            </Link>
                        )}
                    </aside>
                </div>
            </div>
        </>
    );
}
