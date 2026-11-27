import { useForm, usePage } from '@inertiajs/react';
import { CheckCircle2, Send } from 'lucide-react';
import { AnimatePresence, motion } from 'motion/react';
import { BidangTeks, BidangTextarea, PesanGalat } from '@/components/admin/bidang';
import { KepalaHalaman } from '@/components/publik/kepala-halaman';
import { HtmlAman } from '@/components/umum/html-aman';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import type { PropsBersama } from '@/types';

export default function Kontak({
    pengantar,
    terkirim,
}: {
    pengantar: { judul: string; ringkasan: string | null; isi: string | null } | null;
    terkirim: boolean;
}) {
    const { situs } = usePage<PropsBersama>().props;
    const form = useForm({ nama_pengirim: '', surel_pengirim: '', institusi: '', subjek: '', isi_pesan: '', persetujuan_privasi: false, situs_web: '' });

    return (
        <>
            <KepalaHalaman jejak={[{ nama: 'Beranda', url: '/' }, { nama: 'Kontak' }]} judul={pengantar?.judul ?? 'Kontak'} deskripsi={pengantar?.ringkasan} />
            <div className="mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[1fr_1.3fr]">
                <div className="space-y-4">
                    <HtmlAman html={pengantar?.isi} />
                    {situs.kontak.surel && (
                        <p className="text-sm">
                            Surel publik:{' '}
                            <a href={`mailto:${situs.kontak.surel}`} className="font-semibold text-primary underline">
                                {situs.kontak.surel}
                            </a>
                        </p>
                    )}
                </div>
                <div className="rounded-2xl border bg-card p-6 sm:p-8">
                    <AnimatePresence mode="wait" initial={false}>
                        {terkirim && !form.isDirty ? (
                            <motion.div key="terkirim" initial={{ opacity: 0, y: 8 }} animate={{ opacity: 1, y: 0 }} className="py-8 text-center" role="status">
                                <CheckCircle2 className="mx-auto size-12 text-emerald-600" aria-hidden />
                                <h2 className="mt-4 text-xl font-bold">Pesan terkirim</h2>
                                <p className="mt-2 text-muted-foreground">Terima kasih. Pesan Anda sudah diterima dan hanya dapat dibaca pengelola website.</p>
                            </motion.div>
                        ) : (
                            <motion.form
                                key="form"
                                initial={false}
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    form.post('/kontak', { preserveScroll: true, onSuccess: () => form.reset() });
                                }}
                                className="grid gap-4"
                                noValidate
                            >
                                <h2 className="text-xl font-bold">Kirim pesan</h2>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <BidangTeks
                                        id="nama_pengirim"
                                        label="Nama"
                                        wajib
                                        nilai={form.data.nama_pengirim}
                                        onUbah={(v) => form.setData('nama_pengirim', v)}
                                        galat={form.errors.nama_pengirim}
                                        maks={120}
                                        autoComplete="name"
                                    />
                                    <BidangTeks
                                        id="surel_pengirim"
                                        label="Surel"
                                        tipe="email"
                                        wajib
                                        nilai={form.data.surel_pengirim}
                                        onUbah={(v) => form.setData('surel_pengirim', v)}
                                        galat={form.errors.surel_pengirim}
                                        maks={191}
                                        autoComplete="email"
                                    />
                                </div>
                                <BidangTeks
                                    id="institusi"
                                    label="Institusi (opsional)"
                                    nilai={form.data.institusi}
                                    onUbah={(v) => form.setData('institusi', v)}
                                    galat={form.errors.institusi}
                                    maks={150}
                                    autoComplete="organization"
                                />
                                <BidangTeks
                                    id="subjek"
                                    label="Subjek"
                                    wajib
                                    nilai={form.data.subjek}
                                    onUbah={(v) => form.setData('subjek', v)}
                                    galat={form.errors.subjek}
                                    maks={150}
                                />
                                <BidangTextarea
                                    id="isi_pesan"
                                    label="Isi pesan"
                                    wajib
                                    baris={6}
                                    nilai={form.data.isi_pesan}
                                    onUbah={(v) => form.setData('isi_pesan', v)}
                                    galat={form.errors.isi_pesan}
                                    maks={3000}
                                    petunjuk="Maksimal 3.000 karakter. Jangan menuliskan data pribadi anak."
                                />
                                <div className="absolute -left-[9999px]" aria-hidden>
                                    <label htmlFor="situs_web">Situs web</label>
                                    <input
                                        id="situs_web"
                                        tabIndex={-1}
                                        autoComplete="off"
                                        value={form.data.situs_web}
                                        onChange={(e) => form.setData('situs_web', e.target.value)}
                                    />
                                </div>
                                <div className="flex items-start gap-3">
                                    <Checkbox
                                        id="persetujuan_privasi"
                                        checked={form.data.persetujuan_privasi}
                                        onCheckedChange={(v) => form.setData('persetujuan_privasi', v === true)}
                                        aria-invalid={!!form.errors.persetujuan_privasi}
                                    />
                                    <label htmlFor="persetujuan_privasi" className="text-sm leading-snug">
                                        Saya menyetujui penyimpanan data pesan ini sesuai{' '}
                                        <a href="/kebijakan-privasi" className="text-primary underline" target="_blank" rel="noopener">
                                            kebijakan privasi
                                        </a>
                                        .
                                    </label>
                                </div>
                                <PesanGalat pesan={form.errors.persetujuan_privasi} />
                                <Button type="submit" disabled={form.processing} className="w-fit rounded-full px-6">
                                    <Send aria-hidden /> {form.processing ? 'Mengirim…' : 'Kirim pesan'}
                                </Button>
                            </motion.form>
                        )}
                    </AnimatePresence>
                </div>
            </div>
        </>
    );
}
