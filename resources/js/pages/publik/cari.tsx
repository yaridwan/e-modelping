import { Link } from '@inertiajs/react';
import { FormCari } from '@/components/publik/header-publik';
import { KepalaHalaman } from '@/components/publik/kepala-halaman';

type Hasil = { jenis: string; judul: string; konteks: string; cuplikan: string; url: string };

function Sorot({ teks, kata }: { teks: string; kata: string }) {
    if (!kata) {
        return <>{teks}</>;
    }
    const bagian = teks.split(new RegExp(`(${kata.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi'));

    return (
        <>
            {bagian.map((b, i) =>
                b.toLowerCase() === kata.toLowerCase() ? (
                    <mark key={i} className="rounded bg-secondary px-0.5 text-secondary-foreground">
                        {b}
                    </mark>
                ) : (
                    b
                ),
            )}
        </>
    );
}

export default function Cari({ q, hasil }: { q: string; hasil: Hasil[] }) {
    return (
        <>
            <KepalaHalaman
                jejak={[{ nama: 'Beranda', url: '/' }, { nama: 'Pencarian' }]}
                judul={q ? `Hasil pencarian “${q}”` : 'Cari materi'}
                deskripsi="Pencarian mencakup judul, ringkasan, dan isi materi HTML yang sudah terbit (bukan isi berkas PDF)."
            >
                <FormCari className="mt-6 max-w-xl" idInput="cari-halaman" />
            </KepalaHalaman>
            <div className="mx-auto max-w-3xl px-4 py-10 sm:px-6">
                {q && (
                    <p className="mb-6 text-sm text-muted-foreground" role="status">
                        {hasil.length ? `${hasil.length} hasil ditemukan.` : 'Tidak ada materi terbit yang cocok. Coba kata kunci lain.'}
                    </p>
                )}
                <ol className="space-y-4">
                    {hasil.map((h) => (
                        <li key={h.url + h.judul} className="rounded-2xl border bg-card p-5">
                            <p className="text-xs font-bold tracking-wider text-primary uppercase">
                                {h.jenis} · {h.konteks}
                            </p>
                            <h2 className="mt-1 text-lg font-bold">
                                <Link href={h.url} className="hover:text-primary hover:underline">
                                    <Sorot teks={h.judul} kata={q} />
                                </Link>
                            </h2>
                            <p className="mt-1 text-sm text-foreground/75">
                                <Sorot teks={h.cuplikan} kata={q} />
                            </p>
                        </li>
                    ))}
                </ol>
            </div>
        </>
    );
}
