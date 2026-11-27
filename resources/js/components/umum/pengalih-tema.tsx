import { Monitor, Moon, Sun } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';

const PILIHAN: { nilai: Appearance; label: string; ikon: typeof Sun }[] = [
    { nilai: 'light', label: 'Terang', ikon: Sun },
    { nilai: 'dark', label: 'Gelap', ikon: Moon },
    { nilai: 'system', label: 'Ikuti sistem', ikon: Monitor },
];

export function PengalihTema({ onUbah }: { onUbah?: (a: Appearance) => void }) {
    const { appearance, updateAppearance } = useAppearance();

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" aria-label="Pilih tema tampilan">
                    <Sun className="size-5 dark:hidden" aria-hidden />
                    <Moon className="hidden size-5 dark:block" aria-hidden />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-44">
                <DropdownMenuLabel>Tema tampilan</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuRadioGroup
                    value={appearance}
                    onValueChange={(v) => {
                        updateAppearance(v as Appearance);
                        onUbah?.(v as Appearance);
                    }}
                >
                    {PILIHAN.map(({ nilai, label, ikon: Ikon }) => (
                        <DropdownMenuRadioItem key={nilai} value={nilai}>
                            <Ikon className="mr-2 size-4" aria-hidden />
                            {label}
                        </DropdownMenuRadioItem>
                    ))}
                </DropdownMenuRadioGroup>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
