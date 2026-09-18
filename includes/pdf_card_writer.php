<?php
/**
 * pdf_card_writer.php
 * Menghasilkan file PDF berisi banyak kartu pelajar (ukuran KTP/CR-80:
 * 85.6mm x 54mm) tersusun rapi di atas kertas A4, siap dicetak/dipotong.
 * Memakai FPDF (includes/fpdf/fpdf.php) - murni PHP, tanpa composer.
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/fpdf/fpdf.php';

class KartuPelajarPDF extends FPDF
{
    public $cardW = 85.6;
    public $cardH = 54;
    public $marginX = 12;
    public $marginY = 12;
    public $gapX = 6;
    public $gapY = 6;
    public $cols = 2;

    protected function fitImage($path, $x, $y, $maxW, $maxH)
    {
        $size = @getimagesize($path);
        if (!$size) {
            return;
        }
        $ratio = min($maxW / $size[0], $maxH / $size[1]);
        $w = $size[0] * $ratio;
        $h = $size[1] * $ratio;
        try {
            $this->Image($path, $x + ($maxW - $w) / 2, $y + ($maxH - $h) / 2, $w, $h);
        } catch (Exception $e) {
            // Lewati gambar yang tidak didukung (mis. PNG 16-bit) agar
            // proses cetak kolektif tetap lanjut untuk pelajar lainnya.
        }
    }

    /**
     * Menggambar satu kartu pelajar di posisi (x, y).
     * $settings & $student berupa array data, $paths berisi path lokal
     * file logo/foto di server (bukan URL) agar bisa dibaca FPDF.
     */
    public function drawCard($x, $y, array $student, array $settings, $logoPath, $photoPath)
    {
        $w = $this->cardW;
        $h = $this->cardH;
        [$pr, $pg, $pb] = hex_to_rgb($settings['theme_primary'] ?: '#1a3c6e');
        [$sr, $sg, $sb] = hex_to_rgb($settings['theme_secondary'] ?: '#f4b400');
        $cardBaseHex = !empty($settings['theme_card_bg']) ? $settings['theme_card_bg'] : ($settings['theme_primary'] ?: '#1a3c6e');
        [$cr, $cg, $cb] = hex_to_rgb($cardBaseHex);
        $cardOpacityPercent = isset($settings['theme_card_opacity']) ? (int) $settings['theme_card_opacity'] : 12;
        $cardOpacityPercent = max(0, min(40, $cardOpacityPercent));
        $opacity = $cardOpacityPercent / 100;
        $opacityEnd = $opacity * 0.4;

        // 3 titik warna (meniru gradient CSS di versi web): warna kartu
        // di 0%, warna sekunder di 55%, lalu putih polos di 100%.
        $stop0 = [$cr * $opacity + 255 * (1 - $opacity), $cg * $opacity + 255 * (1 - $opacity), $cb * $opacity + 255 * (1 - $opacity)];
        $stop1 = [$sr * $opacityEnd + 255 * (1 - $opacityEnd), $sg * $opacityEnd + 255 * (1 - $opacityEnd), $sb * $opacityEnd + 255 * (1 - $opacityEnd)];
        $stop2 = [255, 255, 255];

        $steps = 18;
        for ($i = 0; $i < $steps; $i++) {
            $t = $i / ($steps - 1);
            if ($t <= 0.55) {
                $f = $t / 0.55;
                $from = $stop0;
                $to = $stop1;
            } else {
                $f = ($t - 0.55) / 0.45;
                $from = $stop1;
                $to = $stop2;
            }
            $r = (int) ($from[0] + ($to[0] - $from[0]) * $f);
            $g = (int) ($from[1] + ($to[1] - $from[1]) * $f);
            $b = (int) ($from[2] + ($to[2] - $from[2]) * $f);
            $this->SetFillColor(max(0, min(255, $r)), max(0, min(255, $g)), max(0, min(255, $b)));
            $this->Rect($x, $y + ($h / $steps) * $i, $w, ($h / $steps) + 0.2, 'F');
        }

        // Border kartu
        $this->SetDrawColor(210, 210, 210);
        $this->SetLineWidth(0.2);
        $this->Rect($x, $y, $w, $h, 'D');

        // Header
        $headerH = 15;
        $this->SetFillColor($pr, $pg, $pb);
        $this->Rect($x, $y, $w, $headerH, 'F');

        $logoBoxSize = 11;
        $textX = $x + 3.5;
        if ($logoPath && file_exists($logoPath)) {
            $this->fitImage($logoPath, $x + 2.5, $y + 2, $logoBoxSize, $logoBoxSize);
            $textX = $x + 2.5 + $logoBoxSize + 1.5;
        }
        $textW = ($x + $w - 3) - $textX;

        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetXY($textX, $y + 2.2);
        $this->Cell($textW, 3.4, $this->txt(mb_strtoupper($settings['school_name'])), 0, 2);

        $this->SetFont('Helvetica', '', 6.2);
        $this->SetXY($textX, $y + 5.8);
        $this->MultiCell($textW, 2.4, $this->txt($settings['address']), 0, 'L');

        // Strip aksen
        $this->SetFillColor($sr, $sg, $sb);
        $this->Rect($x, $y + $headerH, $w, 1.4, 'F');

        // Judul kartu
        $this->SetTextColor($pr, $pg, $pb);
        $this->SetFont('Helvetica', 'B', 7.5);
        $this->SetXY($x, $y + $headerH + 2);
        $this->Cell($w, 3, $this->txt('KARTU PELAJAR'), 0, 0, 'C');

        // Body: foto + info
        $bodyY = $y + $headerH + 6;
        $photoW = 17;
        $photoH = 21;
        $photoX = $x + 3.5;
        $photoY = $bodyY;

        $this->SetDrawColor($pr, $pg, $pb);
        $this->SetLineWidth(0.3);
        $this->Rect($photoX, $photoY, $photoW, $photoH, 'D');
        if ($photoPath && file_exists($photoPath)) {
            $this->fitImage($photoPath, $photoX + 0.15, $photoY + 0.15, $photoW - 0.3, $photoH - 0.3);
        }

        $infoX = $photoX + $photoW + 3;
        $infoW = ($x + $w - 3.5) - $infoX;
        $infoY = $bodyY + 1.5;

        $this->SetTextColor($pr, $pg, $pb);
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetXY($infoX, $infoY);
        $this->Cell($infoW, 3.2, $this->txt(mb_strtoupper($student['full_name'])), 0, 2);

        $this->SetFont('Helvetica', '', 6.3);
        $this->SetTextColor(40, 40, 40);
        $labelW = 13;
        $lineH = 3.3;
        $rows = [
            ['NISN/NIS', $student['nisn'] ?: $student['nis'] ?: '-'],
            ['TTL', ttl($student['pob'], $student['dob'])],
        ];
        $curY = $infoY + 4.5;
        foreach ($rows as $r) {
            $this->SetXY($infoX, $curY);
            $this->SetTextColor(90, 90, 90);
            $this->Cell($labelW, $lineH, $this->txt($r[0]), 0, 0);
            $this->SetTextColor(30, 30, 30);
            $this->SetXY($infoX + $labelW, $curY);
            $this->Cell($infoW - $labelW, $lineH, $this->txt(': ' . $r[1]), 0, 0);
            $curY += $lineH;
        }
        // Alamat (multiline)
        $this->SetXY($infoX, $curY);
        $this->SetTextColor(90, 90, 90);
        $this->Cell($labelW, $lineH, $this->txt('Alamat'), 0, 0);
        $this->SetXY($infoX + $labelW, $curY);
        $this->SetTextColor(30, 30, 30);
        $this->MultiCell($infoW - $labelW, 2.6, $this->txt(': ' . ($student['address'] ?: '-')), 0, 'L');

        // Footer
        $cardNumber = $student['card_number'] ?: ('ID-' . str_pad($student['id'], 5, '0', STR_PAD_LEFT));
        $footerText = $cardNumber . ($settings['card_validity'] ? ' - ' . $settings['card_validity'] : '');
        $this->SetFont('Helvetica', '', 5.3);
        $this->SetTextColor(120, 120, 120);
        $this->SetXY($x + 2, $y + $h - 4.2);
        $this->Cell($w - 4, 3, $this->txt($footerText), 0, 0, 'C');
    }

    /**
     * Konversi UTF-8 ke encoding yang aman untuk font standar FPDF (Latin-1).
     */
    protected function txt($text)
    {
        $text = (string) $text;
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
            if ($converted !== false) {
                return $converted;
            }
        }
        return mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
    }
}

/**
 * Membuat file PDF berisi kartu untuk daftar pelajar, lalu mengembalikan
 * path file sementara. $students = array baris tabel students,
 * $settings = array baris tabel settings.
 */
function generate_cards_pdf(array $students, array $settings)
{
    $pdf = new KartuPelajarPDF('P', 'mm', 'A4');
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins($pdf->marginX, $pdf->marginY, $pdf->marginX);

    $logoPath = null;
    if (!empty($settings['logo'])) {
        $p = UPLOAD_LOGO_DIR . $settings['logo'];
        if (file_exists($p)) {
            $logoPath = $p;
        }
    }

    $perRow = $pdf->cols;
    $cellW = $pdf->cardW + $pdf->gapX;
    $cellH = $pdf->cardH + $pdf->gapY;
    $rowsPerPage = (int) floor((297 - 2 * $pdf->marginY + $pdf->gapY) / $cellH);
    if ($rowsPerPage < 1) {
        $rowsPerPage = 1;
    }
    $perPage = $perRow * $rowsPerPage;

    foreach (array_values($students) as $i => $student) {
        if ($i % $perPage === 0) {
            $pdf->AddPage();
        }
        $posInPage = $i % $perPage;
        $col = $posInPage % $perRow;
        $row = (int) floor($posInPage / $perRow);

        $x = $pdf->marginX + $col * $cellW;
        $y = $pdf->marginY + $row * $cellH;

        $photoPath = null;
        if (!empty($student['photo'])) {
            $p = UPLOAD_PHOTO_DIR . $student['photo'];
            if (file_exists($p)) {
                $photoPath = $p;
            }
        }

        $pdf->drawCard($x, $y, $student, $settings, $logoPath, $photoPath);
    }

    $tmpFile = tempnam(sys_get_temp_dir(), 'cards_pdf_');
    @unlink($tmpFile);
    $tmpFile .= '.pdf';
    $pdf->Output('F', $tmpFile);

    return $tmpFile;
}
