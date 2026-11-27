import { Link } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import { LencanaStatus } from '@/components/admin/daftar';
import { HalamanAdmin } from '@/components/admin/halaman-admin';
import { Button } from '@/components/ui/button';
import { formatTanggal } from '@/lib/utils';

export default function DaftarFase({
    fase,
}: {
    fase: {
        id: number;
        kode: string;
        nama: string;
        judul_indonesia: string;
        durasi: string;
        langkah: number;
        status: string;
        diperbarui_pada: string | null;
    }[];
}) {
    return (
        <HalamanAdmin
            judul="Fase PING"
            jejak={[{ judul: 'Fase PING' }]}
            deskripsi="Tiga fase inti model. Langkah, durasi, contoh dialog, dan media dapat disunting."
        >
            <div className="grid gap-4 md:grid-cols-3">
                {fase.map((f, i) => (
                    <div key={f.id} className="flex flex-col rounded-xl border bg-card p-5">
                        <p className="text-xs font-bold text-primary">
                            Fase {['I', 'II', 'III'][i]} · {f.kode}
                        </p>
                        <h2 className="mt-1 text-lg font-extrabold">{f.nama}</h2>
                        <p className="text-sm text-muted-foreground">{f.judul_indonesia}</p>
                        <p className="mt-3 text-sm">
                            {f.durasi} · {f.langkah} langkah
                        </p>
                        <div className="mt-2">
                            <LencanaStatus status={f.status} />
                        </div>
                        <p className="mt-2 text-xs text-muted-foreground">Diperbarui {formatTanggal(f.diperbarui_pada)}</p>
                        <Button asChild size="sm" className="mt-4 w-fit">
                            <Link href={`/admin/fase/${f.id}/edit`}>
                                <Pencil /> Sunting
                            </Link>
                        </Button>
                    </div>
                ))}
            </div>
        </HalamanAdmin>
    );
}
