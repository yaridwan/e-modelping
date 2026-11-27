import { Link } from '@inertiajs/react';
import { KepalaHalaman } from '@/components/publik/kepala-halaman';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';

export default function Faq({
    pengantar,
    kelompok,
}: {
    pengantar: { judul: string; ringkasan: string | null } | null;
    kelompok: { kategori: string; item: { id: number; pertanyaan: string; jawaban: string }[] }[];
}) {
    return (
        <>
            <KepalaHalaman
                jejak={[{ nama: 'Beranda', url: '/' }, { nama: 'Pertanyaan Umum' }]}
                judul={pengantar?.judul ?? 'Pertanyaan Umum'}
                deskripsi={pengantar?.ringkasan}
            />
            <div className="mx-auto max-w-3xl space-y-10 px-4 py-12 sm:px-6">
                {kelompok.map((k) => (
                    <section key={k.kategori} aria-labelledby={`faq-${k.kategori}`}>
                        <h2 id={`faq-${k.kategori}`} className="mb-3 text-xl font-extrabold">
                            {k.kategori}
                        </h2>
                        <Accordion type="multiple" className="rounded-2xl border bg-card px-5">
                            {k.item.map((f) => (
                                <AccordionItem key={f.id} value={String(f.id)}>
                                    <AccordionTrigger>{f.pertanyaan}</AccordionTrigger>
                                    <AccordionContent className="text-base leading-relaxed text-foreground/80">{f.jawaban}</AccordionContent>
                                </AccordionItem>
                            ))}
                        </Accordion>
                    </section>
                ))}
                {!kelompok.length && <p className="text-muted-foreground">Belum ada pertanyaan yang diterbitkan.</p>}
                <p className="text-sm text-muted-foreground">
                    Pertanyaan Anda belum terjawab?{' '}
                    <Link href="/kontak" className="font-semibold text-primary underline">
                        Kirim pesan
                    </Link>
                    .
                </p>
            </div>
        </>
    );
}
