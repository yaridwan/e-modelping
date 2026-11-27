import * as PopoverPrimitive from '@radix-ui/react-popover';
import type { ReactNode } from 'react';

export function Popover({ pemicu, label, children }: { pemicu: ReactNode; label: string; children: ReactNode }) {
    return (
        <PopoverPrimitive.Root>
            <PopoverPrimitive.Trigger
                aria-label={label}
                className="inline-flex h-8 items-center gap-1.5 rounded-md border border-input bg-background px-3 text-sm font-medium shadow-xs hover:bg-accent focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
            >
                {pemicu}
            </PopoverPrimitive.Trigger>
            <PopoverPrimitive.Portal>
                <PopoverPrimitive.Content
                    align="end"
                    sideOffset={8}
                    className="z-50 rounded-xl border bg-popover p-4 text-popover-foreground shadow-lg data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=open]:zoom-in-95"
                >
                    {children}
                </PopoverPrimitive.Content>
            </PopoverPrimitive.Portal>
        </PopoverPrimitive.Root>
    );
}
