export type ItemNavigasi = {
    id: number;
    label: string;
    href: string;
    eksternal: boolean;
    tab_baru: boolean;
    ikon: string | null;
    anak: ItemNavigasi[];
};

export type TautanSosial = { nama_platform: string; label: string; url: string; ikon: string | null };

export type Situs = {
    nama_aplikasi: string;
    nama_singkat: string;
    tagline: string | null;
    deskripsi_singkat: string | null;
    logo_terang: string | null;
    logo_gelap: string | null;
    /** Lebar ÷ tinggi logo terang (null bila tidak diketahui). */
    logo_rasio?: number | null;
    favicon: string | null;
    warna_utama: string;
    warna_pendamping: string;
    tema_bawaan: 'terang' | 'gelap' | 'sistem';
    teks_footer: string | null;
    tahun_hak_cipta_awal: number | null;
    tahun_sekarang: number;
    nama_pemilik: string | null;
    nama_institusi: string | null;
    kontak: { surel: string | null; whatsapp: string | null; alamat: string | null; kota: string | null; provinsi: string | null };
};

export type PenggunaAdmin = {
    id: number;
    nama_lengkap: string;
    nama_panggilan: string | null;
    surel: string;
    inisial: string;
    foto: string | null;
    tema_pilihan: 'terang' | 'gelap' | 'sistem';
    wajib_ganti_sandi: boolean;
};

export type Seo = {
    judul: string;
    deskripsi: string;
    canonical: string;
    robots: string;
    og: { type: string; site_name: string; locale: string; image: string; image_alt: string };
    twitter: { card: string };
};

export type PropsBersama = {
    situs: Situs;
    navigasi: { utama: ItemNavigasi[]; footer: ItemNavigasi[]; sosial: TautanSosial[] } | null;
    auth: { masuk: boolean; pengguna: PenggunaAdmin | null };
    flash: { sukses: string | null; galat: string | null };
    lencana_admin?: { pesan_baru: number } | null;
    sidebarOpen: boolean;
    seo?: Seo | null;
    errors: Record<string, string>;
};

export type Paginasi<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
};

export type Media = {
    jenis: 'gambar' | 'pdf' | 'video_eksternal';
    url: string | null;
    tautan_asli?: string | null;
    judul: string;
    alt: string;
    lebar: number | null;
    tinggi: number | null;
    keterangan: string | null;
    sumber?: string | null;
    lisensi?: string | null;
};

export type Dokumen = {
    id: number;
    judul: string;
    nomor_versi: string;
    ukuran_bita: number | null;
    jumlah_halaman: number | null;
    diterbitkan_pada: string | null;
    url_baca: string;
    url_berkas: string;
    url_unduh: string | null;
    boleh_unduh: boolean;
};

export type BabRingkas = {
    id: number;
    nomor: number;
    romawi: string;
    judul: string;
    slug: string;
    url: string;
    ringkasan: string | null;
    jumlah_subbab: number;
    waktu_baca_menit: number;
    ada_dokumen: boolean;
    sampul: Media | null;
    diperbarui_pada: string | null;
    subbab?: { judul: string; slug: string }[];
};

export type Dialog = { pembicara: string; ucapan: string };

export type Langkah = {
    id: number;
    nomor: number;
    judul: string;
    penjelasan: string;
    aktivitas_guru: string | null;
    aktivitas_anak: string | null;
    contoh_dialog: Dialog[];
    media: Media | null;
};

export type Fase = {
    id: number;
    kode: string;
    nama: string;
    slug: string;
    url: string;
    judul_indonesia: string;
    deskripsi: string;
    durasi: string;
    durasi_minimum_menit: number | null;
    durasi_maksimum_menit: number | null;
    frekuensi: string | null;
    urutan: number;
    jumlah_langkah: number | null;
    langkah_ringkas: string[];
    tujuan?: string[];
    waktu_pelaksanaan?: string | null;
    catatan_guru?: string | null;
    indikator_keberhasilan?: string[];
    penguatan_karakter?: { aspek: string; bentuk: string }[];
    media?: Media | null;
    bab?: { judul: string; romawi: string; url: string } | null;
    langkah?: Langkah[];
};

export type Indikator = {
    nomor: number;
    rumusan: string;
    contoh_pengamatan: string | null;
    fase_pengamatan: string[];
    catatan_pendampingan: string | null;
    rubrik: { BB: string | null; MB: string | null; BSH: string | null; BSB: string | null };
};

export type Aspek = {
    id: number;
    kode: string;
    nama: string;
    slug: string;
    deskripsi: string;
    contoh_pembiasaan: string | null;
    fokus_pengamatan: string | null;
    ikon: string | null;
    warna: string | null;
    indikator: Indikator[];
};

export type ProfilPenyusun = {
    nama: string;
    inisial: string;
    jabatan: string | null;
    institusi: string | null;
    bidang_keahlian: string | null;
    biografi: string | null;
    situs: string | null;
    foto: Media | null;
    surel_publik: string | null;
    kontak_publik: string | null;
};

export type StatusKonten = 'draf' | 'terbit' | 'arsip';

export type MediaAdmin = {
    id: number;
    uuid?: string;
    judul: string;
    jenis: 'gambar' | 'pdf' | 'video_eksternal';
    url: string | null;
    ekstensi?: string | null;
    tipe_mime?: string | null;
    ukuran_bita?: number;
    lebar?: number | null;
    tinggi?: number | null;
    jumlah_halaman?: number | null;
    teks_alternatif?: string | null;
    dibuat_pada?: string | null;
};

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: PropsBersama;
    }
}
