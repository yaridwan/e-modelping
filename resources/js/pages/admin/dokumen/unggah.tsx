import { useForm } from '@inertiajs/react';
import { FileUp } from 'lucide-react';
import { BidangPilih, BidangSakelar, BidangTeks, BidangTextarea, Bidang } from '@/components/admin/bidang';
import { HalamanAdmin, KartuAdmin } from '@/components/admin/halaman-admin';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Progress } from '@/components/admin/progres';

export default function UnggahDokumen({
    bab,
    bab_terpilih,
    batas_mb,
    panduan_penamaan,
}: {
    bab: { id: number; label: string }[];
    bab_terpilih: number | null;
    batas_mb: number;
    panduan_penamaan: string | null;
}) {
    const form = useForm<{
        jenis_dokumen: string;
        bab_id: string;
        nomor_versi: string;
        judul_dokumen: string;
        catatan_perubahan: string;
        izinkan_unduh: boolean;
        terbitkan_sekarang: boolean;
        berkas: File | null;
    }>({
        jenis_dokumen: 'bab',
        bab_id: bab_terpilih ? String(bab_terpilih) : '',
        nomor_versi: '',
        judul_dokumen: '',
        catatan_perubahan: '',
        izinkan_unduh: true,
        terbitkan_sekarang: false,
        berkas: null,
    });

    return (
        <HalamanAdmin
            judul="Unggah PDF"
            jejak={[{ judul: 'Dokumen PDF', href: '/admin/dokumen' }, { judul: 'Unggah' }]}
            deskripsi={`Berkas disimpan privat sebagai draf. Validasi: ukuran ≤ ${batas_mb} MB, MIME & signature PDF, keterbacaan parser, tanpa enkripsi/JavaScript.`}
        >
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    form.transform((d) => ({ ...d, bab_id: d.jenis_dokumen === 'bab' ? d.bab_id : '' }));
                    form.post('/admin/dokumen', { forceFormData: true });
                }}
            >
                <KartuAdmin>
                    <div className="grid gap-4 md:grid-cols-2">
                        <BidangPilih
                            id="jenis_dokumen"
                            label="Jenis dokumen"
                            wajib
                            nilai={form.data.jenis_dokumen}
                            onUbah={(v) => form.setData('jenis_dokumen', v)}
                            opsi={[
                                { nilai: 'bab', label: 'PDF per bab' },
                                { nilai: 'modul_lengkap', label: 'Modul lengkap' },
                            ]}
                            galat={form.errors.jenis_dokumen}
                        />
                        {form.data.jenis_dokumen === 'bab' && (
                            <BidangPilih
                                id="bab_id"
                                label="Bab"
                                wajib
                                kosong="Pilih bab…"
                                nilai={form.data.bab_id}
                                onUbah={(v) => form.setData('bab_id', v)}
                                opsi={bab.map((b) => ({ nilai: b.id, label: b.label }))}
                                galat={form.errors.bab_id}
                            />
                        )}
                        <BidangTeks
                            id="nomor_versi"
                            label="Nomor versi"
                            wajib
                            placeholder="mis. 1.1"
                            nilai={form.data.nomor_versi}
                            onUbah={(v) => form.setData('nomor_versi', v)}
                            galat={form.errors.nomor_versi}
                            petunjuk={panduan_penamaan ?? undefined}
                        />
                        <BidangTeks
                            id="judul_dokumen"
                            label="Judul dokumen"
                            wajib
                            nilai={form.data.judul_dokumen}
                            onUbah={(v) => form.setData('judul_dokumen', v)}
                            galat={form.errors.judul_dokumen}
                        />
                        <BidangTextarea
                            id="catatan_perubahan"
                            label="Catatan perubahan"
                            className="md:col-span-2"
                            baris={3}
                            nilai={form.data.catatan_perubahan}
                            onUbah={(v) => form.setData('catatan_perubahan', v)}
                            galat={form.errors.catatan_perubahan}
                        />
                        <Bidang
                            id="berkas"
                            label="Berkas PDF"
                            wajib
                            galat={form.errors.berkas}
                            className="md:col-span-2"
                            petunjuk={`Maksimal ${batas_mb} MB. Hanya .pdf.`}
                        >
                            <Input
                                id="berkas"
                                type="file"
                                accept="application/pdf,.pdf"
                                required
                                onChange={(e) => form.setData('berkas', e.target.files?.[0] ?? null)}
                                aria-invalid={!!form.errors.berkas}
                            />
                        </Bidang>
                        <BidangSakelar
                            id="izinkan_unduh"
                            label="Izinkan unduh"
                            petunjuk="Berlaku bila izin unduh global juga aktif. Bukan DRM."
                            nilai={form.data.izinkan_unduh}
                            onUbah={(v) => form.setData('izinkan_unduh', v)}
                        />
                        <BidangSakelar
                            id="terbitkan_sekarang"
                            label="Langsung terbitkan sebagai versi aktif"
                            petunjuk="Disarankan meninjau pratinjau terlebih dahulu."
                            nilai={form.data.terbitkan_sekarang}
                            onUbah={(v) => form.setData('terbitkan_sekarang', v)}
                        />
                    </div>
                    {form.progress && <Progress nilai={form.progress.percentage ?? 0} className="mt-4" />}
                    <Button type="submit" className="mt-5" disabled={form.processing}>
                        <FileUp /> {form.processing ? 'Mengunggah & memvalidasi…' : 'Unggah'}
                    </Button>
                </KartuAdmin>
            </form>
        </HalamanAdmin>
    );
}
