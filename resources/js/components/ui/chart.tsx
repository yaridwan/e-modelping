/**
 * Komponen grafik gaya shadcn/ui (shadcn Charts) di atas Recharts.
 * Ini pustaka visualisasi lokal di browser, bukan layanan analitik eksternal.
 */
import * as React from 'react';
import * as RechartsPrimitive from 'recharts';
import { cn } from '@/lib/utils';

export type ChartConfig = Record<string, { label?: React.ReactNode; color?: string }>;

type ChartContextProps = { config: ChartConfig };

const ChartContext = React.createContext<ChartContextProps | null>(null);

function useChart() {
    const context = React.useContext(ChartContext);
    if (!context) {
        throw new Error('useChart harus dipakai di dalam <ChartContainer />');
    }

    return context;
}

function ChartContainer({
    id,
    className,
    children,
    config,
    ...props
}: React.ComponentProps<'div'> & {
    config: ChartConfig;
    children: React.ComponentProps<typeof RechartsPrimitive.ResponsiveContainer>['children'];
}) {
    const uniqueId = React.useId();
    const chartId = `chart-${id || uniqueId.replace(/:/g, '')}`;

    return (
        <ChartContext.Provider value={{ config }}>
            <div
                data-slot="chart"
                data-chart={chartId}
                className={cn(
                    "flex aspect-video justify-center text-xs [&_.recharts-cartesian-axis-tick_text]:fill-muted-foreground [&_.recharts-cartesian-grid_line[stroke='#ccc']]:stroke-border/60 [&_.recharts-curve.recharts-tooltip-cursor]:stroke-border [&_.recharts-layer]:outline-hidden [&_.recharts-polar-grid_[stroke='#ccc']]:stroke-border [&_.recharts-rectangle.recharts-tooltip-cursor]:fill-muted [&_.recharts-sector]:outline-hidden [&_.recharts-sector[stroke='#fff']]:stroke-transparent [&_.recharts-surface]:outline-hidden",
                    className,
                )}
                {...props}
            >
                <ChartStyle id={chartId} config={config} />
                <RechartsPrimitive.ResponsiveContainer>{children}</RechartsPrimitive.ResponsiveContainer>
            </div>
        </ChartContext.Provider>
    );
}

function ChartStyle({ id, config }: { id: string; config: ChartConfig }) {
    const warna = Object.entries(config).filter(([, c]) => c.color);
    if (!warna.length) {
        return null;
    }

    return (
        <style
            dangerouslySetInnerHTML={{
                __html: `[data-chart=${id}] {\n${warna.map(([kunci, c]) => `  --color-${kunci}: ${c.color};`).join('\n')}\n}`,
            }}
        />
    );
}

const ChartTooltip = RechartsPrimitive.Tooltip;

type TooltipItem = { name?: string | number; value?: number | string; dataKey?: string | number; color?: string; payload?: Record<string, unknown> };

function ChartTooltipContent({
    active,
    payload,
    label,
    labelFormatter,
    valueFormatter,
    hideLabel = false,
    className,
}: {
    active?: boolean;
    payload?: TooltipItem[];
    label?: string | number;
    labelFormatter?: (label: string | number | undefined) => React.ReactNode;
    valueFormatter?: (value: number | string | undefined) => React.ReactNode;
    hideLabel?: boolean;
    className?: string;
}) {
    const { config } = useChart();
    if (!active || !payload?.length) {
        return null;
    }

    return (
        <div className={cn('grid min-w-[9rem] gap-1.5 rounded-lg border bg-popover px-2.5 py-1.5 text-xs text-popover-foreground shadow-xl', className)}>
            {!hideLabel && <div className="font-semibold">{labelFormatter ? labelFormatter(label) : label}</div>}
            {payload.map((item) => {
                const kunci = String(item.dataKey ?? item.name ?? '');
                const c = config[kunci];

                return (
                    <div key={kunci} className="flex items-center justify-between gap-3">
                        <span className="flex items-center gap-1.5 text-muted-foreground">
                            <span className="size-2.5 shrink-0 rounded-[2px]" style={{ background: item.color ?? `var(--color-${kunci})` }} />
                            {c?.label ?? item.name}
                        </span>
                        <span className="font-mono font-semibold tabular-nums">
                            {valueFormatter ? valueFormatter(item.value) : typeof item.value === 'number' ? item.value.toLocaleString('id-ID') : item.value}
                        </span>
                    </div>
                );
            })}
        </div>
    );
}

const ChartLegend = RechartsPrimitive.Legend;

function ChartLegendContent({ payload, className }: { payload?: { value?: string; dataKey?: string | number; color?: string }[]; className?: string }) {
    const { config } = useChart();
    if (!payload?.length) {
        return null;
    }

    return (
        <div className={cn('flex flex-wrap items-center justify-center gap-4 pt-3', className)}>
            {payload.map((item) => {
                const kunci = String(item.dataKey ?? item.value ?? '');

                return (
                    <div key={kunci} className="flex items-center gap-1.5">
                        <span className="size-2.5 rounded-[2px]" style={{ background: item.color }} />
                        <span className="text-muted-foreground">{config[kunci]?.label ?? item.value}</span>
                    </div>
                );
            })}
        </div>
    );
}

export { ChartContainer, ChartLegend, ChartLegendContent, ChartStyle, ChartTooltip, ChartTooltipContent };
