/**
 * Grafik admin (shadcn Charts + Recharts). Modul ini dimuat malas dari halaman dashboard/statistik.
 * Setiap grafik memiliki tinggi kontainer nyata, tooltip berbahasa Indonesia, dan alternatif tabel.
 */
import { Area, AreaChart, Bar, BarChart, CartesianGrid, Cell, Pie, PieChart, XAxis, YAxis } from 'recharts';
import type { ChartConfig } from '@/components/ui/chart';
import { ChartContainer, ChartLegend, ChartLegendContent, ChartTooltip, ChartTooltipContent } from '@/components/ui/chart';
import { formatTanggalPendek } from '@/lib/utils';

function Kosong({ pesan = 'Belum ada data pada periode ini.' }: { pesan?: string }) {
    return <div className="flex h-64 items-center justify-center rounded-lg border border-dashed text-sm text-muted-foreground">{pesan}</div>;
}

export function GrafikTren({
    data,
    seri,
    judul,
}: {
    data: Record<string, number | string>[];
    seri: { kunci: string; label: string; warna: string }[];
    judul: string;
}) {
    const total = data.reduce((n, d) => n + seri.reduce((m, s) => m + Number(d[s.kunci] ?? 0), 0), 0);
    if (!total) {
        return <Kosong />;
    }
    const config: ChartConfig = Object.fromEntries(seri.map((s) => [s.kunci, { label: s.label, color: s.warna }]));

    return (
        <ChartContainer config={config} className="h-64 w-full" role="img" aria-label={judul}>
            <AreaChart data={data} margin={{ left: 4, right: 12, top: 8 }}>
                <defs>
                    {seri.map((s) => (
                        <linearGradient key={s.kunci} id={`isi-${s.kunci}`} x1="0" y1="0" x2="0" y2="1">
                            <stop offset="5%" stopColor={`var(--color-${s.kunci})`} stopOpacity={0.35} />
                            <stop offset="95%" stopColor={`var(--color-${s.kunci})`} stopOpacity={0.02} />
                        </linearGradient>
                    ))}
                </defs>
                <CartesianGrid vertical={false} strokeDasharray="3 3" />
                <XAxis
                    dataKey="tanggal"
                    tickLine={false}
                    axisLine={false}
                    tickMargin={8}
                    minTickGap={24}
                    tickFormatter={(v: string) => formatTanggalPendek(v)}
                />
                <YAxis allowDecimals={false} tickLine={false} axisLine={false} width={32} />
                <ChartTooltip cursor={false} content={<ChartTooltipContent labelFormatter={(l) => formatTanggalPendek(String(l))} />} />
                <ChartLegend content={<ChartLegendContent />} />
                {seri.map((s) => (
                    <Area
                        key={s.kunci}
                        dataKey={s.kunci}
                        name={s.label}
                        type="monotone"
                        stroke={`var(--color-${s.kunci})`}
                        fill={`url(#isi-${s.kunci})`}
                        strokeWidth={2}
                    />
                ))}
            </AreaChart>
        </ChartContainer>
    );
}

export function GrafikBatang({
    data,
    kunciLabel,
    seri,
    judul,
    horizontal = false,
}: {
    data: Record<string, number | string>[];
    kunciLabel: string;
    seri: { kunci: string; label: string; warna: string }[];
    judul: string;
    horizontal?: boolean;
}) {
    if (!data.length) {
        return <Kosong />;
    }
    const config: ChartConfig = Object.fromEntries(seri.map((s) => [s.kunci, { label: s.label, color: s.warna }]));

    return (
        <ChartContainer config={config} className="h-64 w-full" role="img" aria-label={judul}>
            <BarChart data={data} layout={horizontal ? 'vertical' : 'horizontal'} margin={{ left: 4, right: 12 }}>
                <CartesianGrid vertical={horizontal} horizontal={!horizontal} strokeDasharray="3 3" />
                {horizontal ? (
                    <>
                        <XAxis type="number" allowDecimals={false} tickLine={false} axisLine={false} />
                        <YAxis type="category" dataKey={kunciLabel} tickLine={false} axisLine={false} width={64} />
                    </>
                ) : (
                    <>
                        <XAxis
                            dataKey={kunciLabel}
                            tickLine={false}
                            axisLine={false}
                            tickMargin={8}
                            minTickGap={12}
                            tickFormatter={(v: string) => (/^\d{4}-\d{2}-\d{2}$/.test(v) ? formatTanggalPendek(v) : v)}
                        />
                        <YAxis allowDecimals={false} tickLine={false} axisLine={false} width={32} />
                    </>
                )}
                <ChartTooltip cursor={{ fill: 'var(--muted)' }} content={<ChartTooltipContent />} />
                {seri.length > 1 && <ChartLegend content={<ChartLegendContent />} />}
                {seri.map((s) => (
                    <Bar key={s.kunci} dataKey={s.kunci} name={s.label} fill={`var(--color-${s.kunci})`} radius={4} />
                ))}
            </BarChart>
        </ChartContainer>
    );
}

export function GrafikDonat({ data, judul }: { data: { nama: string; jumlah: number }[]; judul: string }) {
    const total = data.reduce((n, d) => n + d.jumlah, 0);
    if (!total) {
        return <Kosong />;
    }
    const warna = ['var(--chart-1)', 'var(--chart-2)', 'var(--chart-3)', 'var(--chart-4)', 'var(--chart-5)'];
    const config: ChartConfig = Object.fromEntries(data.map((d, i) => [d.nama, { label: d.nama, color: warna[i % warna.length] }]));

    return (
        <ChartContainer config={config} className="mx-auto h-64 w-full" role="img" aria-label={judul}>
            <PieChart>
                <ChartTooltip content={<ChartTooltipContent hideLabel />} />
                <Pie data={data} dataKey="jumlah" nameKey="nama" innerRadius={55} outerRadius={90} strokeWidth={2} stroke="var(--card)">
                    {data.map((d, i) => (
                        <Cell key={d.nama} fill={warna[i % warna.length]} />
                    ))}
                </Pie>
                <ChartLegend content={<ChartLegendContent />} />
            </PieChart>
        </ChartContainer>
    );
}
