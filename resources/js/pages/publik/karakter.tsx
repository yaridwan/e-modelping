import { Link } from '@inertiajs/react';
import { IkonDb } from '@/components/umum/ikon';
import { KepalaHalaman } from '@/components/publik/kepala-halaman';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import type { Aspek } from '@/types';

const LABEL_RUBRIK: Record<string, string> = {
    BSB: 'Berkembang Sangat Baik (4)',
    BSH: 'Berkembang Sesuai Harapan (3)',
    MB: 'Mulai Berkembang (2)',
    BB: 'Belum Berkembang (1)',
};

export default function HalamanKarakter({ aspek, fase }: { aspek: Aspek[]; fase: { kode: string; nama: string; url: string }[] }) {
    const namaFase = Object.fromEntries(fase.map((f, i) => [f.kode, `Fase ${['I', 'II', 'III'][i]}`]));

    return (
        <>
            <KepalaHalaman
                jejak={[{ nama: 'Beranda', url: '/' }, { nama: 'Aspek Karakter' }]}
                kecil="Materi referensi"
                judul="Empat aspek dan 16 indikator karakter"
                deskripsi="Setiap aspek dijabarkan menjadi empat indikator perilaku yang dapat diamati guru pada tiga fase PING, lengkap dengan contoh pengamatan dan rubrik BB–BSB. Website tidak menyimpan hasil penilaian anak."
            />
            <div className="mx-auto max-w-5xl space-y-12 px-4 py-10 sm:px-6">
                <p className="rounded-xl border bg-card p-4 text-sm leading-relaxed">
                    Skala rubrik: <strong>BB = 1</strong>, <strong>MB = 2</strong>, <strong>BSH = 3</strong>, <strong>BSB = 4</strong>. Total 16 indikator
                    menghasilkan skor 16–64 dengan persentase skor ÷ 64 × 100%. Perilaku yang belum memperoleh kesempatan pengamatan tidak otomatis diberi BB.
                </p>
                {aspek.map((a) => (
                    <section key={a.id} id={a.slug} aria-labelledby={`judul-${a.slug}`} className="scroll-mt-24">
                        <div className="flex items-start gap-4">
                            <span className="flex size-12 shrink-0 items-center justify-center rounded-xl bg-secondary text-secondary-foreground">
                                <IkonDb nama={a.ikon} className="size-6" />
                            </span>
                            <div>
                                <h2 id={`judul-${a.slug}`} className="text-2xl font-extrabold">
                                    {a.nama}
                                </h2>
                                {a.fokus_pengamatan && <p className="text-sm font-semibold text-muted-foreground">Fokus: {a.fokus_pengamatan}</p>}
                                <p className="mt-2 leading-relaxed text-foreground/80">{a.deskripsi}</p>
                            </div>
                        </div>
                        <Accordion type="multiple" className="mt-5 rounded-2xl border bg-card px-5">
                            {a.indikator.map((i) => (
                                <AccordionItem key={i.nomor} value={String(i.nomor)}>
                                    <AccordionTrigger>
                                        <span>
                                            <span className="mr-2 text-primary tabular-nums">{i.nomor}.</span>
                                            {i.rumusan}
                                        </span>
                                    </AccordionTrigger>
                                    <AccordionContent className="space-y-3 text-[0.95rem] leading-relaxed">
                                        <p>
                                            <strong>Fase pengamatan:</strong>{' '}
                                            {i.fase_pengamatan.length === 3 ? 'Seluruh fase' : i.fase_pengamatan.map((f) => namaFase[f] ?? f).join(', ')}
                                        </p>
                                        {i.contoh_pengamatan && (
                                            <p>
                                                <strong>Contoh pengamatan:</strong> {i.contoh_pengamatan}
                                            </p>
                                        )}
                                        <div className="materi">
                                            <table>
                                                <caption>Rubrik</caption>
                                                <tbody>
                                                    {(['BSB', 'BSH', 'MB', 'BB'] as const).map((k) => (
                                                        <tr key={k}>
                                                            <th scope="row">{LABEL_RUBRIK[k]}</th>
                                                            <td>{i.rubrik[k] ?? '-'}</td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                        {i.catatan_pendampingan && <p className="rounded-lg bg-muted/60 p-3 text-sm">{i.catatan_pendampingan}</p>}
                                    </AccordionContent>
                                </AccordionItem>
                            ))}
                        </Accordion>
                    </section>
                ))}
                <p className="text-sm text-muted-foreground">
                    Rincian instrumen dan penskoran ada di{' '}
                    <Link href="/modul/instrumen-implementasi-model-pembiasaan-ping" className="font-semibold text-primary underline">
                        Bab VII
                    </Link>
                    .
                </p>
            </div>
        </>
    );
}
