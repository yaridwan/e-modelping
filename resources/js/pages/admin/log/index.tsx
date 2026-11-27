import { Halaman, KepalaUrut, KotakCari, LencanaDemo, PilihFilter, terapkanFilter } from '@/components/admin/daftar';
import { HalamanAdmin } from '@/components/admin/halaman-admin';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatTanggal } from '@/lib/utils';
import type { Paginasi } from '@/types';

type L = {
    id: number;
    aksi: string;
    jenis_entitas: string;
    entitas_id: number | null;
    ringkasan: string;
    perubahan: Record<string, unknown> | null;
    pengguna: string | null;
    kode_permintaan: string | null;
    demo: boolean;
    dibuat_pada: string | null;
};

export default function LogAktivitas({
    log,
    filter,
    opsi_aksi,
    opsi_entitas,
}: {
    log: Paginasi<L>;
    filter: { q: string; urut: string; arah: string; per: number; aksi: string; entitas: string; demo: boolean };
    opsi_aksi: string[];
    opsi_entitas: string[];
}) {
    const url = '/admin/log-aktivitas';

    return (
        <HalamanAdmin
            judul="Log Aktivitas"
            jejak={[{ judul: 'Log Aktivitas' }]}
            deskripsi="Catatan audit baca-saja: perubahan konten/pengaturan, login, publikasi, dan penghapusan. Sandi, token, dan isi pesan tidak dicatat."
        >
            <KotakCari url={url} filter={filter} placeholder="Cari ringkasan…">
                <PilihFilter
                    label="Aksi"
                    nilai={filter.aksi}
                    onUbah={(v) => terapkanFilter(url, { ...filter, aksi: v })}
                    opsi={opsi_aksi.map((a) => ({ nilai: a, label: a }))}
                />
                <PilihFilter
                    label="Entitas"
                    nilai={filter.entitas}
                    onUbah={(v) => terapkanFilter(url, { ...filter, entitas: v })}
                    opsi={opsi_entitas.map((a) => ({ nilai: a, label: a }))}
                />
                <label className="flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={filter.demo} onChange={(e) => terapkanFilter(url, { ...filter, demo: e.target.checked })} /> Data demo
                </label>
            </KotakCari>
            <div className="mt-4">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>
                                <KepalaUrut url={url} filter={filter} kolom="dibuat_pada">
                                    Waktu
                                </KepalaUrut>
                            </TableHead>
                            <TableHead>Pelaku</TableHead>
                            <TableHead>
                                <KepalaUrut url={url} filter={filter} kolom="aksi">
                                    Aksi
                                </KepalaUrut>
                            </TableHead>
                            <TableHead>Ringkasan</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {log.data.map((l) => (
                            <TableRow key={l.id}>
                                <TableCell className="text-xs whitespace-nowrap text-muted-foreground">{formatTanggal(l.dibuat_pada, true)}</TableCell>
                                <TableCell className="text-sm">{l.pengguna ?? 'Sistem'}</TableCell>
                                <TableCell className="font-mono text-xs">
                                    {l.aksi}
                                    <span className="block text-muted-foreground">
                                        {l.jenis_entitas}
                                        {l.entitas_id ? `#${l.entitas_id}` : ''}
                                    </span>
                                </TableCell>
                                <TableCell className="text-sm">
                                    {l.ringkasan} {l.demo && <LencanaDemo />}
                                    {l.perubahan && (
                                        <details className="mt-1">
                                            <summary className="cursor-pointer text-xs text-primary">Perubahan (tersaring)</summary>
                                            <pre className="mt-1 max-h-48 overflow-auto rounded bg-muted p-2 text-xs">
                                                {JSON.stringify(l.perubahan, null, 2)}
                                            </pre>
                                        </details>
                                    )}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
                <Halaman data={log} />
            </div>
        </HalamanAdmin>
    );
}
