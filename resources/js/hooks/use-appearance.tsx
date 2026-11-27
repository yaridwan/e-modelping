import { useSyncExternalStore } from 'react';

export type ResolvedAppearance = 'light' | 'dark';
export type Appearance = ResolvedAppearance | 'system';

const listeners = new Set<() => void>();
let currentAppearance: Appearance | null = null;

const prefersDark = (): boolean => typeof window !== 'undefined' && window.matchMedia('(prefers-color-scheme: dark)').matches;

const setCookie = (name: string, value: string, days = 365): void => {
    if (typeof document === 'undefined') {
        return;
    }
    document.cookie = `${name}=${value};path=/;max-age=${days * 86400};SameSite=Lax`;
};

/** Tema awal mengikuti atribut data-tema yang ditetapkan server (cookie/profil/bawaan situs). */
const awal = (): Appearance => {
    if (typeof document === 'undefined') {
        return 'system';
    }
    try {
        const simpan = localStorage.getItem('appearance') as Appearance | null;
        if (simpan === 'light' || simpan === 'dark' || simpan === 'system') {
            return simpan;
        }
    } catch {
        /* abaikan */
    }
    const atribut = document.documentElement.getAttribute('data-tema') as Appearance | null;

    return atribut ?? 'system';
};

const isDark = (a: Appearance): boolean => a === 'dark' || (a === 'system' && prefersDark());

const applyTheme = (a: Appearance): void => {
    if (typeof document === 'undefined') {
        return;
    }
    const gelap = isDark(a);
    document.documentElement.classList.toggle('dark', gelap);
    document.documentElement.style.colorScheme = gelap ? 'dark' : 'light';
};

const subscribe = (cb: () => void) => {
    listeners.add(cb);

    return () => listeners.delete(cb);
};

export function initializeTheme(): void {
    if (typeof window === 'undefined') {
        return;
    }
    currentAppearance = awal();
    applyTheme(currentAppearance);
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        applyTheme(currentAppearance ?? 'system');
        listeners.forEach((l) => l());
    });
}

export function useAppearance() {
    const appearance = useSyncExternalStore(
        subscribe,
        () => currentAppearance ?? awal(),
        () => 'system' as Appearance,
    );
    const resolvedAppearance: ResolvedAppearance = isDark(appearance) ? 'dark' : 'light';

    const updateAppearance = (mode: Appearance): void => {
        currentAppearance = mode;
        try {
            localStorage.setItem('appearance', mode);
        } catch {
            /* abaikan */
        }
        setCookie('appearance', mode);
        applyTheme(mode);
        listeners.forEach((l) => l());
    };

    return { appearance, resolvedAppearance, updateAppearance } as const;
}
