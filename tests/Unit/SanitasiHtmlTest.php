<?php

namespace Tests\Unit;

use App\Layanan\LayananMedia;
use App\Layanan\LayananSanitasiHtml;
use App\Support\EksporCsv;
use Tests\TestCase;

class SanitasiHtmlTest extends TestCase
{
    public function test_payload_xss_dibuang(): void
    {
        $s = app(LayananSanitasiHtml::class);
        $kotor = '<h2 onclick="x()">Judul</h2><script>alert(1)</script><p>Teks <a href="javascript:alert(1)">klik</a> <a href="https://contoh.test">aman</a></p>'
            .'<img src="x" onerror="alert(1)"><iframe src="https://jahat.test"></iframe><p style="color:red">gaya</p>'
            .'<a href="data:text/html;base64,PHNjcmlwdD4=">data</a><svg><script>alert(2)</script></svg><table><tr><td>sel</td></tr></table>';
        $bersih = $s->bersihkan($kotor);

        $this->assertStringNotContainsString('<script', $bersih);
        $this->assertStringNotContainsString('onclick', $bersih);
        $this->assertStringNotContainsString('onerror', $bersih);
        $this->assertStringNotContainsString('javascript:', $bersih);
        $this->assertStringNotContainsString('<iframe', $bersih);
        $this->assertStringNotContainsString('style=', $bersih);
        $this->assertStringNotContainsString('data:text/html', $bersih);
        $this->assertStringNotContainsString('<svg', $bersih);
        $this->assertStringContainsString('<h2>Judul</h2>', $bersih);
        $this->assertStringContainsString('href="https://contoh.test"', $bersih);
        $this->assertStringContainsString('rel="noopener noreferrer nofollow"', $bersih);
        $this->assertStringContainsString('<td>sel</td>', $bersih);
    }

    public function test_validasi_url_menolak_protokol_berbahaya(): void
    {
        foreach (['javascript:alert(1)', 'JaVaScRiPt:alert(1)', 'data:text/html,x', '//jahat.test/x', 'vbscript:x', '/jalur dengan spasi', 'ftp://x.test'] as $url) {
            $this->assertFalse(LayananSanitasiHtml::urlAman($url), $url);
        }
        foreach (['/modul', '/modul/sintaks-model#a', 'https://contoh.test/a?b=1', 'mailto:a@contoh.test', '#bagian'] as $url) {
            $this->assertTrue(LayananSanitasiHtml::urlAman($url), $url);
        }
    }

    public function test_embed_video_hanya_penyedia_diizinkan(): void
    {
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', LayananMedia::urlEmbedVideo('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', LayananMedia::urlEmbedVideo('https://youtu.be/dQw4w9WgXcQ'));
        $this->assertSame('https://player.vimeo.com/video/123456789?dnt=1', LayananMedia::urlEmbedVideo('https://vimeo.com/123456789'));
        $this->assertNull(LayananMedia::urlEmbedVideo('https://jahat.test/watch?v=dQw4w9WgXcQ'));
        $this->assertNull(LayananMedia::urlEmbedVideo('http://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertNull(LayananMedia::urlEmbedVideo('http://127.0.0.1/admin'));
    }

    public function test_csv_mencegah_formula_injection(): void
    {
        $this->assertSame("'=HYPERLINK(\"x\")", EksporCsv::selAman('=HYPERLINK("x")'));
        $this->assertSame("'+1", EksporCsv::selAman('+1'));
        $this->assertSame("'-2", EksporCsv::selAman('-2'));
        $this->assertSame("'@SUM(A1)", EksporCsv::selAman('@SUM(A1)'));
        $this->assertSame('Teks biasa', EksporCsv::selAman('Teks biasa'));
    }
}
