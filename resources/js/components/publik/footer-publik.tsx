import { Link, usePage } from '@inertiajs/react';
import { Mail, MapPin, MessageCircle } from 'lucide-react';
import { IkonSosial } from '@/components/umum/ikon';
import { Logo } from '@/components/umum/logo';
import type { PropsBersama } from '@/types';

export function FooterPublik() {
    const { situs, navigasi } = usePage<PropsBersama>().props;
    const k = situs.kontak;
    const tahun =
        situs.tahun_hak_cipta_awal && situs.tahun_hak_cipta_awal < situs.tahun_sekarang
            ? `${situs.tahun_hak_cipta_awal}–${situs.tahun_sekarang}`
            : String(situs.tahun_sekarang);
    const lokasi = [k.alamat, k.kota, k.provinsi].filter(Boolean).join(', ');
    const wa = k.whatsapp ? k.whatsapp.replace(/[^0-9]/g, '').replace(/^0/, '62') : null;

    return (
        <footer className="mt-20 border-t bg-card">
            <div className="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-[1.4fr_1fr_1fr]">
                <div className="space-y-4">
                    <Logo tinggi={52} />
                    {situs.deskripsi_singkat && <p className="max-w-md text-sm leading-relaxed text-muted-foreground">{situs.deskripsi_singkat}</p>}
                    {situs.teks_footer && <p className="max-w-md text-sm text-muted-foreground">{situs.teks_footer}</p>}
                </div>
                <nav aria-label="Tautan footer">
                    <h2 className="mb-3 text-sm font-bold">Tautan</h2>
                    <ul className="space-y-2 text-sm">
                        {(navigasi?.footer ?? []).map((item) => (
                            <li key={item.id}>
                                {item.eksternal ? (
                                    <a
                                        href={item.href}
                                        rel="noopener noreferrer"
                                        target={item.tab_baru ? '_blank' : undefined}
                                        className="text-muted-foreground hover:text-primary"
                                    >
                                        {item.label}
                                    </a>
                                ) : (
                                    <Link href={item.href} className="text-muted-foreground hover:text-primary">
                                        {item.label}
                                    </Link>
                                )}
                            </li>
                        ))}
                    </ul>
                </nav>
                <div>
                    <h2 className="mb-3 text-sm font-bold">Kontak publik</h2>
                    <ul className="space-y-2 text-sm text-muted-foreground">
                        {k.surel && (
                            <li className="flex items-center gap-2">
                                <Mail className="size-4 shrink-0" aria-hidden />
                                <a href={`mailto:${k.surel}`} className="hover:text-primary">
                                    {k.surel}
                                </a>
                            </li>
                        )}
                        {wa && (
                            <li className="flex items-center gap-2">
                                <MessageCircle className="size-4 shrink-0" aria-hidden />
                                <a href={`https://wa.me/${wa}`} rel="noopener noreferrer" target="_blank" className="hover:text-primary">
                                    WhatsApp {k.whatsapp}
                                </a>
                            </li>
                        )}
                        {lokasi && (
                            <li className="flex items-start gap-2">
                                <MapPin className="mt-0.5 size-4 shrink-0" aria-hidden />
                                {lokasi}
                            </li>
                        )}
                        <li>
                            <Link href="/kontak" className="font-semibold text-primary hover:underline">
                                Kirim pesan melalui formulir
                            </Link>
                        </li>
                    </ul>
                    {!!navigasi?.sosial.length && (
                        <ul className="mt-4 flex gap-2" aria-label="Media sosial">
                            {navigasi.sosial.map((s) => (
                                <li key={s.url}>
                                    <a
                                        href={s.url}
                                        rel="noopener noreferrer me"
                                        target="_blank"
                                        aria-label={s.label}
                                        className="flex size-9 items-center justify-center rounded-full border text-muted-foreground hover:border-primary hover:text-primary"
                                    >
                                        <IkonSosial platform={s.nama_platform} className="size-4" />
                                    </a>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
            <div className="border-t">
                <div className="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-5 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <p>
                        © {tahun} {situs.nama_pemilik || situs.nama_aplikasi}. Hak cipta materi pada penyusun.
                    </p>
                    <p className="flex gap-4">
                        <Link href="/kebijakan-privasi" className="hover:text-primary">
                            Kebijakan privasi
                        </Link>
                        <Link href="/panduan-penggunaan" className="hover:text-primary">
                            Panduan penggunaan
                        </Link>
                        <a href="/admin/masuk" rel="nofollow" className="hover:text-primary">
                            Masuk admin
                        </a>
                    </p>
                </div>
            </div>
        </footer>
    );
}
