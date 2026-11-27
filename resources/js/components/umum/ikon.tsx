import {
    BookOpen,
    CircleHelp,
    ExternalLink,
    FileText,
    Globe,
    HandHeart,
    HeartPulse,
    House,
    Images,
    Info,
    Layers,
    ListChecks,
    Mail,
    MessageCircle,
    MessagesSquare,
    Search,
    Shield,
    ShieldCheck,
    Smile,
    Sparkles,
    Star,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

/** Allowlist ikon (nilai ikon dari database dipetakan ke komponen; nilai lain diabaikan). */
const PETA: Record<string, LucideIcon> = {
    house: House,
    'book-open': BookOpen,
    info: Info,
    images: Images,
    'circle-help': CircleHelp,
    mail: Mail,
    shield: Shield,
    'file-text': FileText,
    layers: Layers,
    sparkles: Sparkles,
    search: Search,
    'external-link': ExternalLink,
    'list-checks': ListChecks,
    'hand-heart': HandHeart,
    'heart-pulse': HeartPulse,
    'messages-square': MessagesSquare,
    smile: Smile,
    'shield-check': ShieldCheck,
    star: Star,
    whatsapp: MessageCircle,
    situs: Globe,
};

export function IkonDb({ nama, className }: { nama: string | null | undefined; className?: string }) {
    const Ikon = nama ? PETA[nama] : undefined;

    return Ikon ? <Ikon className={className} aria-hidden /> : null;
}

/** Ikon platform sosial sederhana (SVG lokal, tanpa skrip pihak ketiga). */
export function IkonSosial({ platform, className }: { platform: string; className?: string }) {
    const jalur: Record<string, string> = {
        instagram:
            'M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5Zm5 5a5 5 0 1 0 0 10 5 5 0 0 0 0-10Zm6.5-.8a1.2 1.2 0 1 0 0 2.4 1.2 1.2 0 0 0 0-2.4Z',
        youtube:
            'M23 7.5s-.2-1.6-.9-2.3c-.9-.9-1.9-.9-2.3-1C16.6 4 12 4 12 4s-4.6 0-7.8.2c-.5.1-1.5.1-2.3 1C1.2 5.9 1 7.5 1 7.5S.8 9.4.8 11.2v1.6c0 1.9.2 3.7.2 3.7s.2 1.6.9 2.3c.9.9 2 .9 2.5 1 1.8.2 7.6.2 7.6.2s4.6 0 7.8-.2c.5-.1 1.5-.1 2.3-1 .7-.7.9-2.3.9-2.3s.2-1.9.2-3.7v-1.6c0-1.9-.2-3.7-.2-3.7ZM9.7 14.9V8.6l6 3.2-6 3.1Z',
        facebook: 'M14 8h3V4h-3c-2.8 0-4 1.8-4 4.3V10H7v4h3v8h4v-8h3l.5-4H14V8.6c0-.4.3-.6.6-.6Z',
        tiktok: 'M16 3c.3 2.1 1.6 3.6 4 3.8v3.4c-1.5 0-2.8-.4-4-1.2v6.2A5.8 5.8 0 1 1 10.2 9.4v3.5a2.4 2.4 0 1 0 2.4 2.4V3H16Z',
        x: 'M17.7 3H21l-7.2 8.2L22 21h-6.6l-5.2-6.3L4.3 21H1l7.7-8.8L1 3h6.8l4.7 5.7L17.7 3Z',
        linkedin:
            'M4.5 3.5a2 2 0 1 1 0 4 2 2 0 0 1 0-4ZM3 9h3v12H3V9Zm6 0h2.9v1.7c.4-.8 1.5-1.9 3.4-1.9 3.6 0 4.2 2.4 4.2 5.4V21h-3v-6c0-1.4 0-3.3-2-3.3s-2.4 1.6-2.4 3.2V21H9V9Z',
    };
    const d = jalur[platform];
    if (!d) {
        return <IkonDb nama={platform === 'whatsapp' ? 'whatsapp' : 'situs'} className={className} />;
    }

    return (
        <svg viewBox="0 0 24 24" className={className} fill="currentColor" aria-hidden="true">
            <path d={d} />
        </svg>
    );
}
