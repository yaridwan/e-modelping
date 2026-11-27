"""Pisahkan PDF sumber E-Model PING menjadi PDF per bab + salinan modul lengkap.

Alat bantu penyusun (bukan runtime aplikasi). Membutuhkan pypdf (pip install pypdf).

Pemakaian:
    python -I pisah_pdf.py "E-Model PING_Revisi 1.pdf" ../../database/seeders/aset/sumber

Pemetaan halaman PDF (1-based, inklusif). Halaman cetak N = halaman PDF N + 8.
Bab VII berakhir dan Bab VIII dimulai pada halaman PDF 102 yang sama, serta Bab IX
berakhir di halaman 118 tempat "Pesan untuk Guru PAUD" dimulai. Halaman bersama tersebut
disertakan pada kedua dokumen; pemotongan di tengah halaman TIDAK dilakukan.
"""

from __future__ import annotations

import hashlib
import json
import os
import sys

from pypdf import PdfReader, PdfWriter

JUMLAH_HALAMAN = 121
PEMETAAN = [
    (1, "I", 9, 13, None),
    (2, "II", 14, 39, None),
    (3, "III", 40, 58, None),
    (4, "IV", 59, 79, None),
    (5, "V", 80, 84, None),
    (6, "VI", 85, 94, None),
    (7, "VII", 95, 102, "Halaman 102 juga memuat awal Bab VIII (dimulai di tengah halaman)."),
    (8, "VIII", 102, 111, "Halaman 102 juga memuat akhir Bab VII."),
    (9, "IX", 112, 118, "Halaman 118 juga memuat awal 'Pesan untuk Guru PAUD'."),
]


def sha256(jalur: str) -> str:
    h = hashlib.sha256()
    with open(jalur, "rb") as f:
        for blok in iter(lambda: f.read(65536), b""):
            h.update(blok)
    return h.hexdigest()


def main(sumber: str, keluar: str) -> None:
    pembaca = PdfReader(sumber)
    if len(pembaca.pages) != JUMLAH_HALAMAN:
        sys.exit(f"Jumlah halaman {len(pembaca.pages)} tidak sesuai harapan {JUMLAH_HALAMAN}; pemetaan perlu ditinjau.")
    os.makedirs(keluar, exist_ok=True)
    manifest = {
        "sumber": os.path.basename(sumber),
        "sha256_sumber": sha256(sumber),
        "jumlah_halaman": JUMLAH_HALAMAN,
        "selisih_halaman_cetak": 8,
        "dokumen": [],
    }

    lengkap = os.path.join(keluar, "modul-lengkap.pdf")
    penulis = PdfWriter()
    for halaman in pembaca.pages:
        penulis.add_page(halaman)
    penulis.add_metadata({"/Title": "E-Model Pembiasaan PING (modul lengkap)"})
    with open(lengkap, "wb") as f:
        penulis.write(f)
    manifest["dokumen"].append({"jenis": "modul_lengkap", "berkas": "modul-lengkap.pdf", "halaman_pdf_awal": 1,
                                "halaman_pdf_akhir": JUMLAH_HALAMAN, "jumlah_halaman": JUMLAH_HALAMAN, "sha256": sha256(lengkap), "catatan": None})

    for nomor, romawi, awal, akhir, catatan in PEMETAAN:
        penulis = PdfWriter()
        for i in range(awal - 1, akhir):
            penulis.add_page(pembaca.pages[i])
        penulis.add_metadata({"/Title": f"E-Model Pembiasaan PING - Bab {romawi}"})
        nama = f"bab-{nomor:02d}.pdf"
        jalur = os.path.join(keluar, nama)
        with open(jalur, "wb") as f:
            penulis.write(f)
        manifest["dokumen"].append({"jenis": "bab", "nomor_bab": nomor, "romawi": romawi, "berkas": nama,
                                    "halaman_pdf_awal": awal, "halaman_pdf_akhir": akhir,
                                    "halaman_cetak_awal": awal - 8, "halaman_cetak_akhir": akhir - 8,
                                    "jumlah_halaman": akhir - awal + 1, "sha256": sha256(jalur), "catatan": catatan})

    with open(os.path.join(keluar, "manifest.json"), "w", encoding="utf-8") as f:
        json.dump(manifest, f, ensure_ascii=False, indent=2)
    print(json.dumps([(d["berkas"], d["jumlah_halaman"]) for d in manifest["dokumen"]]))


if __name__ == "__main__":
    if len(sys.argv) != 3:
        print(__doc__)
        sys.exit(1)
    main(sys.argv[1], sys.argv[2])
