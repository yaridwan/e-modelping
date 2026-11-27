import { AlertTriangle, ChevronLeft, ChevronRight, Loader2, Maximize2, ZoomIn, ZoomOut } from 'lucide-react';
import type { PDFDocumentProxy, RenderTask } from 'pdfjs-dist';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';

/**
 * Penampil PDF berbasis PDF.js (dimuat malas, khusus browser). Worker berasal dari paket
 * pdfjs-dist versi yang sama. Memakai permintaan HTTP Range (206) untuk mengambil potongan
 * berkas sesuai kebutuhan; server hanya mencatat satu peristiwa baca per hari.
 */
export default function PenampilPdf({ url, judul }: { url: string; judul: string }) {
    const kanvas = useRef<HTMLCanvasElement>(null);
    const wadah = useRef<HTMLDivElement>(null);
    const [doc, setDoc] = useState<PDFDocumentProxy | null>(null);
    const [halaman, setHalaman] = useState(1);
    const [skala, setSkala] = useState(1);
    const [status, setStatus] = useState<'memuat' | 'siap' | 'galat'>('memuat');
    const [pesan, setPesan] = useState('');

    useEffect(() => {
        let batal = false;
        let tugasMuat: { destroy: () => Promise<void> } | null = null;
        void (async () => {
            try {
                const pdfjs = await import('pdfjs-dist');
                const worker = (await import('pdfjs-dist/build/pdf.worker.min.mjs?url')).default;
                pdfjs.GlobalWorkerOptions.workerSrc = worker;
                const tugas = pdfjs.getDocument({ url, rangeChunkSize: 262144, disableAutoFetch: true, disableStream: true });
                tugasMuat = tugas;
                const d = await tugas.promise;
                if (!batal) {
                    setDoc(d);
                    setStatus('siap');
                }
            } catch (e) {
                if (!batal) {
                    setStatus('galat');
                    setPesan(e instanceof Error ? e.message : 'Dokumen tidak dapat dimuat.');
                }
            }
        })();

        return () => {
            batal = true;
            void tugasMuat?.destroy();
        };
    }, [url]);

    useEffect(() => {
        if (!doc || !kanvas.current) {
            return;
        }
        let tugas: RenderTask | null = null;
        let batal = false;
        void (async () => {
            const page = await doc.getPage(halaman);
            if (batal || !kanvas.current) {
                return;
            }
            const lebarWadah = wadah.current?.clientWidth ?? 800;
            const dasar = page.getViewport({ scale: 1 });
            const skalaPas = Math.min(2, (lebarWadah - 16) / dasar.width) * skala;
            const rasio = window.devicePixelRatio || 1;
            const viewport = page.getViewport({ scale: skalaPas * rasio });
            const c = kanvas.current;
            c.width = viewport.width;
            c.height = viewport.height;
            c.style.width = `${viewport.width / rasio}px`;
            c.style.height = `${viewport.height / rasio}px`;
            tugas = page.render({ canvas: c, viewport });
            await tugas.promise.catch(() => undefined);
        })();

        return () => {
            batal = true;
            tugas?.cancel();
        };
    }, [doc, halaman, skala]);

    const total = doc?.numPages ?? 0;

    return (
        <div className="overflow-hidden rounded-xl border bg-muted/40">
            <div className="flex flex-wrap items-center gap-1 border-b bg-card px-2 py-1.5" role="toolbar" aria-label="Kontrol penampil PDF">
                <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => setHalaman((h) => Math.max(1, h - 1))}
                    disabled={halaman <= 1 || status !== 'siap'}
                    aria-label="Halaman sebelumnya"
                >
                    <ChevronLeft />
                </Button>
                <label className="flex items-center gap-1 text-sm">
                    <span className="sr-only">Nomor halaman</span>
                    <input
                        type="number"
                        min={1}
                        max={total || 1}
                        value={halaman}
                        onChange={(e) => setHalaman(Math.min(Math.max(1, Number(e.target.value) || 1), total || 1))}
                        className="h-8 w-14 rounded-md border bg-background px-1 text-center"
                        disabled={status !== 'siap'}
                    />
                    <span className="text-muted-foreground">/ {total || '–'}</span>
                </label>
                <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => setHalaman((h) => Math.min(total, h + 1))}
                    disabled={halaman >= total || status !== 'siap'}
                    aria-label="Halaman berikutnya"
                >
                    <ChevronRight />
                </Button>
                <span className="mx-1 h-5 w-px bg-border" aria-hidden />
                <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => setSkala((s) => Math.max(0.5, +(s - 0.25).toFixed(2)))}
                    disabled={status !== 'siap'}
                    aria-label="Perkecil"
                >
                    <ZoomOut />
                </Button>
                <span className="w-12 text-center text-sm tabular-nums" aria-live="polite">
                    {Math.round(skala * 100)}%
                </span>
                <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => setSkala((s) => Math.min(3, +(s + 0.25).toFixed(2)))}
                    disabled={status !== 'siap'}
                    aria-label="Perbesar"
                >
                    <ZoomIn />
                </Button>
                <Button variant="ghost" size="icon" onClick={() => setSkala(1)} disabled={status !== 'siap'} aria-label="Sesuaikan lebar">
                    <Maximize2 />
                </Button>
            </div>
            <div ref={wadah} className="flex max-h-[80vh] min-h-72 justify-center overflow-auto p-2" aria-busy={status === 'memuat'}>
                {status === 'memuat' && (
                    <p className="flex items-center gap-2 self-center text-sm text-muted-foreground" role="status">
                        <Loader2 className="size-4 animate-spin" aria-hidden /> Memuat dokumen…
                    </p>
                )}
                {status === 'galat' && (
                    <p className="flex max-w-md items-start gap-2 self-center text-sm text-destructive" role="alert">
                        <AlertTriangle className="mt-0.5 size-4 shrink-0" aria-hidden /> Dokumen gagal dimuat di penampil. Gunakan tombol “Buka di tab baru”.{' '}
                        {pesan && <span className="sr-only">({pesan})</span>}
                    </p>
                )}
                <canvas ref={kanvas} className={status === 'siap' ? 'bg-white shadow' : 'hidden'} aria-label={`Halaman ${halaman} dari ${judul}`} role="img" />
            </div>
        </div>
    );
}
