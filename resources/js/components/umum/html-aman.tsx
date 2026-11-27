import { cn } from '@/lib/utils';

/**
 * Menampilkan HTML materi. HANYA menerima HTML yang sudah disanitasi server
 * (App\Layanan\LayananSanitasiHtml) — tidak pernah HTML mentah dari pengguna.
 */
export function HtmlAman({ html, className, id }: { html: string | null | undefined; className?: string; id?: string }) {
    if (!html) {
        return null;
    }

    return <div id={id} className={cn('materi', className)} dangerouslySetInnerHTML={{ __html: html }} />;
}
