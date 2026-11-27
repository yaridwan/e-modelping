import { Link, useForm } from '@inertiajs/react';
import { Archive, ArchiveRestore, ArrowLeft, CheckCheck, ChevronLeft, ChevronRight, Copy, History, Mail, Reply, Save, Trash2, UserRound } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { StatusPesan } from '@/components/admin/aksi-pesan';
import { salinTeks, tautanBalas, ubahStatus } from '@/components/admin/aksi-pesan';
import { BidangPilih, BidangTextarea } from '@/components/admin/bidang';
import { LencanaDemo, LencanaStatus } from '@/components/admin/daftar';
import { HalamanAdmin, KartuAdmin } from '@/components/admin/halaman-admin';
import { TombolKonfirmasi } from '@/components/admin/konfirmasi';
import { Button } from '@/components/ui/button';
import { formatTanggal } from '@/lib/utils';

type P = {
    id: number;
    nama_pengirim: string;
    surel_pengirim: string;
    institusi: string | null;
    subjek: string;
    isi_pesan: string;
    catatan_admin: string | null;
    persetujuan_privasi: boolean;
    data_contoh: boolean;
    status: StatusPesan;
    dibuat_pada: string | null;
    dibaca_pada: string | null;
    ditindaklanjuti_pada: string | null;
};
type Ringkas = { id: number; subjek: string; status: string; dibuat_pada: string | null };
type Riwayat = { id: number; ringkasan: string; oleh: string | null; pada: string | null };

function FormTindakLanjut({ pesan }: { pesan: P }) {
    const form = useForm({ status: pesan.status as string, catatan_admin: pesan.catatan_admin ?? '' });

    // Selaraskan pilihan status bila diubah lewat tombol aksi cepat.
    useEffect(() => {
        form.setData('status', pesan.status);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [pesan.status]);

    return (
        <form
            className="grid gap-3"
            onSubmit={(e) => {
                e.preventDefault();
                form.patch(`/admin/pesan/${pesan.id}`, { preserveScroll: true });
            }}
        >
            <BidangPilih
                id="status"
                label="Status"
                nilai={form.data.status}
                onUbah={(v) => form.setData('status', v)}
                opsi={[
                    { nilai: 'baru', label: 'Baru' },
                    { nilai: 'dibaca', label: 'Dibaca' },
                    { nilai: 'ditindaklanjuti', label: 'Ditindaklanjuti' },
                    { nilai: 'arsip', label: 'Arsip' },
                ]}
                galat={form.errors.status}
            />
            <BidangTextarea
                id="catatan"
                label="Catatan internal"
                petunjuk="Hanya terlihat oleh admin, mis. ringkasan jawaban atau tindak lanjut."
                baris={5}
                maks={3000}
                nilai={form.data.catatan_admin}
                onUbah={(v) => form.setData('catatan_admin', v)}
                galat={form.errors.catatan_admin}
            />
            <Button type="submit" disabled={form.processing || !form.isDirty}>
                <Save /> {form.processing ? 'Menyimpan…' : 'Simpan'}
            </Button>
            <dl className="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-xs text-muted-foreground">
                <dt>Diterima</dt>
                <dd>{formatTanggal(pesan.dibuat_pada, true)}</dd>
                <dt>Dibaca</dt>
                <dd>{formatTanggal(pesan.dibaca_pada, true)}</dd>
                <dt>Ditindaklanjuti</dt>
                <dd>{formatTanggal(pesan.ditindaklanjuti_pada, true)}</dd>
            </dl>
        </form>
    );
}

export default function DetailPesan({
    pesan,
    navigasi,
    pesan_lain,
    riwayat,
}: {
    pesan: P;
    navigasi: { sebelumnya: number | null; berikutnya: number | null };
    pesan_lain: { total: number; data: Ringkas[] };
    riwayat: Riwayat[];
}) {
    const [sudahBalas, setSudahBalas] = useState(false);
    const [proses, setProses] = useState(false);
    const status = pesan.status;
    const ubah = (s: StatusPesan) => {
        setProses(true);
        ubahStatus(pesan.id, s, () => setProses(false));
    };

    // Reset petunjuk balasan saat berpindah ke pesan lain.
    useEffect(() => setSudahBalas(false), [pesan.id]);

    return (
        <HalamanAdmin
            judul={pesan.subjek}
            jejak={[{ judul: 'Pesan Masuk', href: '/admin/pesan' }, { judul: pesan.subjek }]}
            deskripsi={
                <>
                    <LencanaStatus status={status} /> {pesan.data_contoh && <LencanaDemo />}
                </>
            }
            aksi={
                <>
                    <Button asChild variant="ghost" size="sm">
                        <Link href="/admin/pesan">
                            <ArrowLeft /> Daftar
                        </Link>
                    </Button>
                    <div className="flex rounded-md border">
                        {navigasi.sebelumnya ? (
                            <Button asChild variant="ghost" size="sm" className="rounded-r-none">
                                <Link href={`/admin/pesan/${navigasi.sebelumnya}`} aria-label="Pesan lebih baru">
                                    <ChevronLeft /> Lebih baru
                                </Link>
                            </Button>
                        ) : (
                            <Button variant="ghost" size="sm" className="rounded-r-none" disabled>
                                <ChevronLeft /> Lebih baru
                            </Button>
                        )}
                        {navigasi.berikutnya ? (
                            <Button asChild variant="ghost" size="sm" className="rounded-l-none border-l">
                                <Link href={`/admin/pesan/${navigasi.berikutnya}`} aria-label="Pesan lebih lama">
                                    Lebih lama <ChevronRight />
                                </Link>
                            </Button>
                        ) : (
                            <Button variant="ghost" size="sm" className="rounded-l-none border-l" disabled>
                                Lebih lama <ChevronRight />
                            </Button>
                        )}
                    </div>
                </>
            }
        >
            {/* Bilah aksi pesan */}
            <div className="mb-6 flex flex-wrap items-center gap-2 rounded-xl border bg-card p-3" role="toolbar" aria-label="Aksi pesan">
                <Button asChild size="sm">
                    <a
                        href={tautanBalas({
                            surel: pesan.surel_pengirim,
                            subjek: pesan.subjek,
                            nama: pesan.nama_pengirim,
                            isi: pesan.isi_pesan,
                            dibuat_pada: pesan.dibuat_pada,
                        })}
                        onClick={() => setSudahBalas(true)}
                    >
                        <Reply /> Balas lewat surel
                    </a>
                </Button>
                <Button size="sm" variant="outline" onClick={() => void salinTeks(pesan.surel_pengirim, 'Alamat surel')}>
                    <Copy /> Salin surel
                </Button>
                {status !== 'ditindaklanjuti' && (
                    <Button size="sm" variant="outline" disabled={proses} onClick={() => ubah('ditindaklanjuti')}>
                        <CheckCheck /> Tandai ditindaklanjuti
                    </Button>
                )}
                <Button size="sm" variant="outline" disabled={proses} onClick={() => ubah('baru')}>
                    <Mail /> Tandai belum dibaca
                </Button>
                {status === 'arsip' ? (
                    <Button size="sm" variant="outline" disabled={proses} onClick={() => ubah('dibaca')}>
                        <ArchiveRestore /> Pulihkan ke kotak masuk
                    </Button>
                ) : (
                    <Button size="sm" variant="outline" disabled={proses} onClick={() => ubah('arsip')}>
                        <Archive /> Arsipkan
                    </Button>
                )}
                <div className="ml-auto">
                    <TombolKonfirmasi
                        url={`/admin/pesan/${pesan.id}`}
                        judul="Hapus pesan ini secara permanen?"
                        deskripsi={
                            <>
                                Pesan “{pesan.subjek}” dari {pesan.nama_pengirim} beserta catatan internalnya akan dihapus permanen dan{' '}
                                <strong>tidak dapat dipulihkan</strong>. Jika hanya ingin merapikan kotak masuk, gunakan Arsipkan.
                            </>
                        }
                        labelAksi="Hapus permanen"
                    >
                        <Trash2 /> Hapus
                    </TombolKonfirmasi>
                </div>
            </div>

            {sudahBalas && status !== 'ditindaklanjuti' && (
                <div className="mb-6 flex flex-wrap items-center gap-3 rounded-xl border border-primary/30 bg-primary/5 p-3 text-sm" role="status">
                    <span>Sudah mengirim balasan dari aplikasi surel?</span>
                    <Button size="sm" disabled={proses} onClick={() => ubah('ditindaklanjuti')}>
                        <CheckCheck /> Ya, tandai ditindaklanjuti
                    </Button>
                    <Button size="sm" variant="ghost" onClick={() => setSudahBalas(false)}>
                        Nanti
                    </Button>
                </div>
            )}

            <div className="grid gap-6 lg:grid-cols-[1fr_22rem]">
                <div className="space-y-6">
                    <KartuAdmin>
                        <dl className="mb-4 grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="text-muted-foreground">Dari</dt>
                                <dd className="font-semibold">{pesan.nama_pengirim}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Surel</dt>
                                <dd>
                                    <a href={`mailto:${pesan.surel_pengirim}`} className="break-all text-primary hover:underline">
                                        {pesan.surel_pengirim}
                                    </a>
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Institusi</dt>
                                <dd>{pesan.institusi ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Diterima</dt>
                                <dd>{formatTanggal(pesan.dibuat_pada, true)}</dd>
                            </div>
                        </dl>
                        <p className="rounded-lg bg-muted/50 p-4 leading-relaxed break-words whitespace-pre-line">{pesan.isi_pesan}</p>
                        <p className="mt-3 text-xs text-muted-foreground">
                            Persetujuan privasi: {pesan.persetujuan_privasi ? 'ya' : 'tidak'}. Aplikasi tidak mengirim balasan otomatis; tombol “Balas lewat
                            surel” membuka aplikasi surel Anda dengan subjek dan kutipan pesan.
                        </p>
                    </KartuAdmin>

                    {pesan_lain.total > 0 && (
                        <KartuAdmin
                            judul={
                                <span className="flex items-center gap-2">
                                    <UserRound className="size-4" aria-hidden /> Pesan lain dari pengirim ini ({pesan_lain.total})
                                </span>
                            }
                        >
                            <ul className="divide-y">
                                {pesan_lain.data.map((m) => (
                                    <li key={m.id} className="flex items-center justify-between gap-3 py-2">
                                        <Link href={`/admin/pesan/${m.id}`} className="line-clamp-1 text-sm font-medium hover:text-primary">
                                            {m.subjek}
                                        </Link>
                                        <span className="flex shrink-0 items-center gap-2 text-xs text-muted-foreground">
                                            <LencanaStatus status={m.status} />
                                            {formatTanggal(m.dibuat_pada)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                            {pesan_lain.total > pesan_lain.data.length && (
                                <Link
                                    href={`/admin/pesan?status=semua&q=${encodeURIComponent(pesan.surel_pengirim)}`}
                                    className="mt-2 inline-block text-sm text-primary hover:underline"
                                >
                                    Lihat semua
                                </Link>
                            )}
                        </KartuAdmin>
                    )}
                </div>

                <div className="space-y-6">
                    <KartuAdmin judul="Tindak lanjut">
                        <FormTindakLanjut key={pesan.id} pesan={pesan} />
                    </KartuAdmin>

                    <KartuAdmin
                        judul={
                            <span className="flex items-center gap-2">
                                <History className="size-4" aria-hidden /> Riwayat
                            </span>
                        }
                    >
                        {riwayat.length ? (
                            <ol className="relative space-y-3 border-l pl-4">
                                {riwayat.map((r) => (
                                    <li key={r.id} className="text-sm">
                                        <span className="absolute -left-[5px] mt-1.5 size-2.5 rounded-full border-2 border-background bg-primary" aria-hidden />
                                        <p className="font-medium">{r.ringkasan}</p>
                                        <p className="text-xs text-muted-foreground">
                                            {formatTanggal(r.pada, true)}
                                            {r.oleh ? ` · ${r.oleh}` : ''}
                                        </p>
                                    </li>
                                ))}
                            </ol>
                        ) : (
                            <p className="text-sm text-muted-foreground">Belum ada riwayat.</p>
                        )}
                    </KartuAdmin>
                </div>
            </div>
        </HalamanAdmin>
    );
}
