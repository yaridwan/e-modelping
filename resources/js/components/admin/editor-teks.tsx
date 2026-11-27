import Image from '@tiptap/extension-image';
import { Table, TableCell, TableHeader, TableRow } from '@tiptap/extension-table';
import { EditorContent, useEditor, useEditorState } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { Bold, Heading2, Heading3, ImagePlus, Italic, Link2, List, ListOrdered, Quote, Redo2, Table2, Underline, Undo2, Unlink } from 'lucide-react';
import type { ReactNode } from 'react';
import { useEffect, useState } from 'react';
import { PemilihMedia } from '@/components/admin/pemilih-media';
import { cn } from '@/lib/utils';

function TombolAlat({
    aktif,
    onClick,
    label,
    children,
    nonaktif,
}: {
    aktif?: boolean;
    onClick: () => void;
    label: string;
    children: ReactNode;
    nonaktif?: boolean;
}) {
    return (
        <button
            type="button"
            onMouseDown={(e) => e.preventDefault()}
            onClick={onClick}
            disabled={nonaktif}
            aria-label={label}
            aria-pressed={aktif}
            title={label}
            className={cn(
                'inline-flex size-8 items-center justify-center rounded-md text-sm hover:bg-accent disabled:opacity-40',
                aktif && 'bg-secondary text-secondary-foreground',
            )}
        >
            {children}
        </button>
    );
}

/**
 * Editor teks kaya (heading, daftar, tautan, tabel, kutipan, gambar). Keluaran HTML tetap
 * disanitasi ulang di server sebelum disimpan. Aman SSR (immediatelyRender: false).
 */
export function EditorTeks({ id, nilai, onUbah, label, galat }: { id: string; nilai: string; onUbah: (html: string) => void; label: string; galat?: string }) {
    const [pilihGambar, setPilihGambar] = useState(false);
    const editor = useEditor({
        immediatelyRender: false,
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3, 4] },
                link: { openOnClick: false, autolink: true, protocols: ['http', 'https', 'mailto'], HTMLAttributes: { rel: 'noopener noreferrer nofollow' } },
            }),
            Image.configure({ inline: false }),
            Table.configure({ resizable: false }),
            TableRow,
            TableHeader,
            TableCell,
        ],
        content: nilai,
        editorProps: {
            attributes: { class: 'materi tiptap px-4 py-3 focus:outline-none', id, 'aria-label': label, 'aria-multiline': 'true', role: 'textbox' },
        },
        onUpdate: ({ editor: e }) => onUbah(e.getHTML()),
    });

    const status = useEditorState({
        editor,
        selector: ({ editor: e }) =>
            e
                ? {
                      h2: e.isActive('heading', { level: 2 }),
                      h3: e.isActive('heading', { level: 3 }),
                      bold: e.isActive('bold'),
                      italic: e.isActive('italic'),
                      underline: e.isActive('underline'),
                      bullet: e.isActive('bulletList'),
                      ordered: e.isActive('orderedList'),
                      quote: e.isActive('blockquote'),
                      link: e.isActive('link'),
                      table: e.isActive('table'),
                  }
                : null,
    });

    useEffect(() => {
        if (editor && nilai !== editor.getHTML() && !editor.isFocused) {
            editor.commands.setContent(nilai, { emitUpdate: false });
        }
    }, [nilai, editor]);

    const aturTautan = () => {
        if (!editor) {
            return;
        }
        const lama = editor.getAttributes('link').href as string | undefined;
        const url = window.prompt('Alamat tautan (https://… atau /jalur-internal):', lama ?? 'https://');
        if (url === null) {
            return;
        }
        if (url === '') {
            editor.chain().focus().unsetLink().run();

            return;
        }
        if (!/^(https?:\/\/|mailto:|\/)/i.test(url)) {
            window.alert('Tautan harus diawali https://, http://, mailto:, atau / (jalur internal).');

            return;
        }
        editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
    };

    return (
        <div className={cn('overflow-hidden rounded-lg border bg-background', galat && 'border-destructive')}>
            <div className="flex flex-wrap items-center gap-0.5 border-b bg-muted/40 p-1" role="toolbar" aria-label={`Alat format ${label}`}>
                <TombolAlat label="Judul bagian (H2)" aktif={status?.h2} onClick={() => editor?.chain().focus().toggleHeading({ level: 2 }).run()}>
                    <Heading2 className="size-4" />
                </TombolAlat>
                <TombolAlat label="Subjudul (H3)" aktif={status?.h3} onClick={() => editor?.chain().focus().toggleHeading({ level: 3 }).run()}>
                    <Heading3 className="size-4" />
                </TombolAlat>
                <span className="mx-1 h-5 w-px bg-border" aria-hidden />
                <TombolAlat label="Tebal" aktif={status?.bold} onClick={() => editor?.chain().focus().toggleBold().run()}>
                    <Bold className="size-4" />
                </TombolAlat>
                <TombolAlat label="Miring" aktif={status?.italic} onClick={() => editor?.chain().focus().toggleItalic().run()}>
                    <Italic className="size-4" />
                </TombolAlat>
                <TombolAlat label="Garis bawah" aktif={status?.underline} onClick={() => editor?.chain().focus().toggleUnderline().run()}>
                    <Underline className="size-4" />
                </TombolAlat>
                <span className="mx-1 h-5 w-px bg-border" aria-hidden />
                <TombolAlat label="Daftar berpoin" aktif={status?.bullet} onClick={() => editor?.chain().focus().toggleBulletList().run()}>
                    <List className="size-4" />
                </TombolAlat>
                <TombolAlat label="Daftar bernomor" aktif={status?.ordered} onClick={() => editor?.chain().focus().toggleOrderedList().run()}>
                    <ListOrdered className="size-4" />
                </TombolAlat>
                <TombolAlat label="Kutipan" aktif={status?.quote} onClick={() => editor?.chain().focus().toggleBlockquote().run()}>
                    <Quote className="size-4" />
                </TombolAlat>
                <span className="mx-1 h-5 w-px bg-border" aria-hidden />
                <TombolAlat label="Sisipkan/ubah tautan" aktif={status?.link} onClick={aturTautan}>
                    <Link2 className="size-4" />
                </TombolAlat>
                <TombolAlat label="Hapus tautan" nonaktif={!status?.link} onClick={() => editor?.chain().focus().unsetLink().run()}>
                    <Unlink className="size-4" />
                </TombolAlat>
                <TombolAlat label="Sisipkan tabel 3×3" onClick={() => editor?.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()}>
                    <Table2 className="size-4" />
                </TombolAlat>
                {status?.table && (
                    <>
                        <button type="button" className="rounded px-2 text-xs hover:bg-accent" onClick={() => editor?.chain().focus().addRowAfter().run()}>
                            + Baris
                        </button>
                        <button type="button" className="rounded px-2 text-xs hover:bg-accent" onClick={() => editor?.chain().focus().addColumnAfter().run()}>
                            + Kolom
                        </button>
                        <button
                            type="button"
                            className="rounded px-2 text-xs text-destructive hover:bg-accent"
                            onClick={() => editor?.chain().focus().deleteTable().run()}
                        >
                            Hapus tabel
                        </button>
                    </>
                )}
                <TombolAlat label="Sisipkan gambar dari pustaka media" onClick={() => setPilihGambar(true)}>
                    <ImagePlus className="size-4" />
                </TombolAlat>
                <span className="ml-auto" />
                <TombolAlat label="Urungkan" onClick={() => editor?.chain().focus().undo().run()}>
                    <Undo2 className="size-4" />
                </TombolAlat>
                <TombolAlat label="Ulangi" onClick={() => editor?.chain().focus().redo().run()}>
                    <Redo2 className="size-4" />
                </TombolAlat>
            </div>
            {editor ? <EditorContent editor={editor} /> : <div className="materi min-h-56 px-4 py-3 text-muted-foreground">Memuat editor…</div>}
            <PemilihMedia
                buka={pilihGambar}
                onTutup={() => setPilihGambar(false)}
                jenis="gambar"
                onPilih={(m) => {
                    if (m.uuid && m.ekstensi) {
                        editor
                            ?.chain()
                            .focus()
                            .setImage({ src: `/media/${m.uuid}.${m.ekstensi}`, alt: m.teks_alternatif ?? m.judul })
                            .run();
                    }
                    setPilihGambar(false);
                }}
            />
        </div>
    );
}
