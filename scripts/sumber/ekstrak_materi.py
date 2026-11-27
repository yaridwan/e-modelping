"""Ekstraksi materi HTML per subbab dari PDF sumber E-Model PING.

Alat bantu penyusun (bukan bagian runtime aplikasi). Membaca keluaran
`pdftotext -layout -enc UTF-8` yang sudah dipisah per halaman, lalu menyusun
HTML sederhana per subbab beserta provenance halaman (halaman cetak dan
halaman PDF). Tabel dan gambar tidak dikonversi otomatis; posisinya ditandai
agar penyunting dapat menggantinya dengan tabel kurasi atau merujuk PDF.

Pemakaian:
    pdftotext -layout -enc UTF-8 "E-Model PING_Revisi 1.pdf" layout.txt
    python -I ekstrak_materi.py layout.txt keluaran.json

Selisih halaman: halaman cetak N = halaman PDF N + 8 (halaman 1-8 adalah
sampul, kata pengantar, dan daftar isi/tabel/gambar).
"""

from __future__ import annotations

import html
import json
import re
import sys

SELISIH = 8

# (nomor bab, romawi, judul, halaman PDF awal, halaman PDF akhir, subbab)
# subbab: (huruf, judul, halaman PDF tempat heading berada)
STRUKTUR = [
    (1, "I", "Pendahuluan", 9, 13, [
        ("A", "Latar Belakang", 9), ("B", "Urgensi Model", 10),
        ("C", "Permasalahan Karakter Anak di Era Digital", 11),
        ("D", "Dasar Pengembangan Model PING", 12)]),
    (2, "II", "Landasan Teori", 14, 39, [
        ("A", "Pendidikan Karakter Anak Usia Dini", 14), ("B", "Pembiasaan 3S dan 5S", 18),
        ("C", "Transisi Kegiatan Pembelajaran", 23), ("D", "Perkembangan Sosial Emosional", 28),
        ("E", "Konsep Model Pembiasaan PING", 33)]),
    (3, "III", "Konsep Model Pembiasaan PING", 40, 58, [
        ("A", "Filosofi Model", 40), ("B", "Tujuan Model", 43), ("C", "Sasaran Pengguna", 46),
        ("D", "Keunggulan Model", 51), ("E", "Karakteristik Model", 55)]),
    (4, "IV", "Sintaks Model", 59, 79, [
        ("A", "Pre-Class Greeting", 60), ("B", "Inter-Class Transition Greeting", 66),
        ("C", "End-Day Closing Greeting", 73)]),
    (5, "V", "Peran Guru", 80, 84, [
        ("A", "Peran Guru Sebelum Kegiatan Pembelajaran", 80),
        ("B", "Peran Guru Selama Kegiatan Pembelajaran", 81),
        ("C", "Peran Guru Setelah Kegiatan Pembelajaran", 83)]),
    (6, "VI", "Indikator Karakter", 85, 94, [
        ("A", "Karakter Disiplin", 85), ("B", "Karakter Tanggung Jawab", 87),
        ("C", "Karakter Kontrol Diri", 89), ("D", "Karakter Sopan Santun", 91)]),
    (7, "VII", "Instrumen Implementasi Model Pembiasaan PING", 95, 101, [
        ("A", "Lembar Observasi", 95), ("B", "Petunjuk Penskoran", 98),
        ("C", "Format Rekapitulasi Nilai", 100)]),
    (8, "VIII", "Contoh Implementasi Model Pembiasaan PING", 102, 111, [
        ("A", "Skenario Pembelajaran", 103), ("B", "Contoh Kegiatan", 103),
        ("C", "Foto Implementasi", 105), ("D", "Refleksi Guru", 108)]),
    (9, "IX", "Evaluasi Implementasi Model Pembiasaan PING", 112, 118, [
        ("A", "Evaluasi Guru", 112), ("B", "Evaluasi Anak", 114),
        ("C", "Refleksi Pelaksanaan", 115)]),
]

RE_NOMOR_HALAMAN = re.compile(r"^\s*(\d{1,3}|[ivxlc]+)\s*$")
RE_CELAH = re.compile(r"\S\s{3,}\S")
RE_TABEL = re.compile(r"^\s*Tabel\s+(\d+\.\d+)\s+(.*)$")
RE_GAMBAR = re.compile(r"^\s*Gambar\s+(\d+\.\d+)\s+(.*)$")
RE_SUBJUDUL = re.compile(r"^(\d{1,2})\.\s+(.{2,90})$")
RE_HURUF = re.compile(r"^([a-z])\.\s+(.*)$")
RE_ANGKA_KURUNG = re.compile(r"^\s*(\d{1,2})\)\s+(.*)$")
RE_BUTIR = re.compile(r"^\s*[•\-–▪●○☐✓]\s*(.*)$")
RE_DIALOG = re.compile(r'^\s*(Guru|Anak|Anak-anak|Orang tua)\s*:\s*(.*)$')


def bersihkan(teks: str) -> str:
    return re.sub(r"\s+", " ", teks).strip()


def esc(teks: str) -> str:
    return html.escape(bersihkan(teks), quote=False)


def gabung(sebelumnya: str, baris: str) -> str:
    baris = baris.strip()
    if sebelumnya.endswith("-") and not sebelumnya.endswith(" -"):
        return sebelumnya + baris
    return f"{sebelumnya} {baris}" if sebelumnya else baris


def muat_halaman(berkas: str) -> list[list[str]]:
    teks = open(berkas, encoding="utf-8").read()
    halaman = teks.split("\f")
    hasil = []
    for isi in halaman:
        baris = [b.rstrip() for b in isi.splitlines() if b.strip()]
        while baris and RE_NOMOR_HALAMAN.match(baris[-1]):
            baris.pop()
        hasil.append(baris)
    return hasil


class Penyusun:
    def __init__(self, halaman_cetak_awal: int) -> None:
        self.blok: list[str] = []
        self.paragraf = ""
        self.daftar: list[str] = []
        self.jenis_daftar = ""
        self.tabel: list[dict] = []
        self.gambar: list[dict] = []
        self.mode_tabel = False
        self.halaman = halaman_cetak_awal

    def tutup_paragraf(self) -> None:
        if self.paragraf:
            m = RE_DIALOG.match(self.paragraf)
            if m:
                self.blok.append(f"<p><strong>{esc(m.group(1))}:</strong> {esc(m.group(2))}</p>")
            else:
                self.blok.append(f"<p>{esc(self.paragraf)}</p>")
            self.paragraf = ""

    def tutup_daftar(self) -> None:
        if self.daftar:
            tag = "ul" if self.jenis_daftar == "ul" else "ol"
            atribut = ' type="a"' if self.jenis_daftar == "a" else ""
            butir = "".join(f"<li>{esc(b)}</li>" for b in self.daftar)
            self.blok.append(f"<{tag}{atribut}>{butir}</{tag}>")
            self.daftar = []
            self.jenis_daftar = ""

    def tutup(self) -> None:
        self.tutup_paragraf()
        self.tutup_daftar()

    def tambah_daftar(self, jenis: str, teks: str) -> None:
        self.tutup_paragraf()
        if self.jenis_daftar and self.jenis_daftar != jenis:
            self.tutup_daftar()
        self.jenis_daftar = jenis
        self.daftar.append(teks)

    def proses(self, baris: str) -> None:
        indent = len(baris) - len(baris.lstrip(" "))
        isi = baris.strip()

        m = RE_TABEL.match(baris)
        if m:
            self.tutup()
            self.mode_tabel = True
            nomor, judul = m.group(1), bersihkan(m.group(2))
            self.tabel.append({"nomor": nomor, "judul": judul, "halaman": self.halaman})
            self.blok.append(f"<!--TABEL:{nomor}-->")
            return
        m = RE_GAMBAR.match(baris)
        if m:
            self.tutup()
            self.mode_tabel = False
            nomor, judul = m.group(1), bersihkan(m.group(2))
            self.gambar.append({"nomor": nomor, "judul": judul, "halaman": self.halaman})
            self.blok.append(f"<!--GAMBAR:{nomor}-->")
            return

        if self.mode_tabel:
            paragraf_baru = 5 <= indent <= 14 and not RE_CELAH.search(isi) and len(isi) > 55
            judul_baru = indent == 0 and RE_SUBJUDUL.match(isi) and not RE_CELAH.search(isi)
            if not (paragraf_baru or judul_baru):
                return
            self.mode_tabel = False

        m = re.match(r"^([a-z])\.\s+(Langkah\s+\d+)\s*(.*)$", isi) if indent <= 12 else None
        if m:
            self.tutup()
            self.blok.append(f"<h4>{esc(m.group(2))}</h4>")
            if m.group(3):
                self.paragraf = m.group(3)
            return
        m = re.search(r"^(.*\S)\s+([a-z])\.\s+(Langkah\s+\d+)\s*$", isi)
        if m:
            self.paragraf = gabung(self.paragraf, m.group(1)) if self.paragraf else m.group(1)
            self.tutup()
            self.blok.append(f"<h4>{esc(m.group(3))}</h4>")
            return
        m = RE_SUBJUDUL.match(isi) if indent <= 2 else None
        if m and len(isi) <= 75 and not isi.endswith((".", ";", ",")):
            self.tutup()
            self.blok.append(f"<h3>{esc(isi)}</h3>")
            return
        m = RE_HURUF.match(isi) if indent <= 2 else None
        if m:
            teks = m.group(2)
            if re.match(r"^Langkah\s+\d+\s*$", teks):
                self.tutup()
                self.blok.append(f"<h4>{esc(teks)}</h4>")
                return
            self.tambah_daftar("a", teks)
            return
        m = RE_ANGKA_KURUNG.match(baris)
        if m and indent <= 6:
            self.tambah_daftar("1", m.group(2))
            return
        m = RE_BUTIR.match(baris)
        if m and m.group(1):
            self.tambah_daftar("ul", m.group(1))
            return
        if RE_DIALOG.match(isi):
            self.tutup()
            self.paragraf = isi
            return
        if indent >= 5:
            self.tutup()
            self.paragraf = isi
            return
        # baris lanjutan
        if self.daftar and not self.paragraf:
            self.daftar[-1] = gabung(self.daftar[-1], isi)
        else:
            self.paragraf = gabung(self.paragraf, isi)


def rapikan_butir(blok: list[str]) -> list[str]:
    """Ubah deretan >=3 paragraf pendek berurutan menjadi daftar tak bernomor."""
    hasil: list[str] = []
    antrian: list[str] = []

    def pendek(b: str) -> bool:
        return (b.startswith("<p>") and not b.startswith("<p><strong>")
                and len(b) <= 110 and not b.rstrip("</p>").endswith(":"))

    def kosongkan() -> None:
        if len(antrian) >= 3:
            hasil.append("<ul>" + "".join("<li>" + b[3:-4] + "</li>" for b in antrian) + "</ul>")
        else:
            hasil.extend(antrian)
        antrian.clear()

    for b in blok:
        if pendek(b):
            antrian.append(b)
        else:
            kosongkan()
            hasil.append(b)
    kosongkan()
    return hasil


def susun(halaman: list[list[str]]) -> list[dict]:
    hasil = []
    for nomor, romawi, judul, awal, akhir, subbab in STRUKTUR:
        aliran: list[tuple[int, str]] = []
        for p in range(awal, akhir + 1):
            for b in halaman[p - 1]:
                aliran.append((p, b))
        # buang judul bab
        aliran = [(p, b) for p, b in aliran
                  if not re.match(rf"^\s*BAB\s+{romawi}\s*$", b)
                  and bersihkan(b).upper() != judul.upper()]
        posisi = []
        for huruf, jdl, hal in subbab:
            pola = re.compile(rf"^\s{{0,3}}{huruf}\.\s*{re.escape(jdl.split()[0])}", re.I)
            idx = next((i for i, (p, b) in enumerate(aliran) if p >= hal and pola.match(b)), None)
            posisi.append(idx)
        daftar_sub = []
        pengantar_baris = aliran[: posisi[0]] if posisi[0] else []
        for i, (huruf, jdl, hal) in enumerate(subbab):
            mulai = posisi[i]
            if mulai is None:
                daftar_sub.append({"huruf": huruf, "judul": jdl, "html": "", "tidak_ditemukan": True,
                                   "halaman_pdf_awal": hal, "halaman_pdf_akhir": hal})
                continue
            selesai = next((posisi[j] for j in range(i + 1, len(subbab)) if posisi[j] is not None), len(aliran))
            potongan = aliran[mulai:selesai]
            heading = potongan[0][1]
            sisa_heading = re.sub(rf"^\s*{huruf}\.\s*{re.escape(jdl)}", "", heading, flags=re.I).strip()
            penyusun = Penyusun(potongan[0][0] - SELISIH)
            if sisa_heading:
                penyusun.paragraf = sisa_heading
            for p, b in potongan[1:]:
                penyusun.halaman = p - SELISIH
                penyusun.proses(b)
            penyusun.tutup()
            daftar_sub.append({
                "huruf": huruf, "judul": jdl,
                "halaman_pdf_awal": potongan[0][0], "halaman_pdf_akhir": potongan[-1][0],
                "halaman_cetak_awal": potongan[0][0] - SELISIH, "halaman_cetak_akhir": potongan[-1][0] - SELISIH,
                "html": "\n".join(penyusun.blok), "tabel": penyusun.tabel, "gambar": penyusun.gambar,
            })
        pengantar = Penyusun(awal - SELISIH)
        for p, b in pengantar_baris:
            pengantar.proses(b)
        pengantar.tutup()
        hasil.append({
            "nomor_bab": nomor, "nomor_romawi": romawi, "judul": judul,
            "halaman_pdf_awal": awal, "halaman_pdf_akhir": akhir,
            "halaman_cetak_awal": awal - SELISIH, "halaman_cetak_akhir": akhir - SELISIH,
            "pengantar_html": "\n".join(pengantar.blok), "subbab": daftar_sub,
        })
    return hasil


if __name__ == "__main__":
    if len(sys.argv) != 3:
        print(__doc__)
        sys.exit(1)
    halaman = muat_halaman(sys.argv[1])
    if len(halaman) < 121:
        sys.exit(f"Jumlah halaman tidak sesuai: {len(halaman)} (diharapkan 121)")
    data = susun(halaman)
    json.dump({"sumber": "E-Model PING_Revisi 1.pdf", "jumlah_halaman_pdf": 121,
               "selisih_halaman_cetak": SELISIH, "bab": data},
              open(sys.argv[2], "w", encoding="utf-8"), ensure_ascii=False, indent=1)
    for bab in data:
        print(bab["nomor_romawi"], bab["judul"], [ (s["huruf"], len(s["html"])) for s in bab["subbab"]])
