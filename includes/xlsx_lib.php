<?php
/**
 * xlsx_lib.php
 * -----------------------------------------------------------
 * Library XLSX ringan TANPA dependensi composer/library luar.
 * Cukup memakai ekstensi PHP bawaan: ZipArchive + SimpleXML
 * (keduanya umum tersedia di shared hosting / cPanel).
 *
 * Kemampuan sengaja dibatasi hanya untuk kebutuhan aplikasi ini:
 * - Menulis 1 sheet berisi teks (import/export data pelajar)
 * - Membaca 1 sheet pertama dari file .xlsx (mendukung file yang
 *   dibuat aplikasi ini maupun yang disimpan ulang dari MS Excel)
 * -----------------------------------------------------------
 */

function xlsx_supported()
{
    return class_exists('ZipArchive') && class_exists('SimpleXMLElement');
}

function xlsx_col_letter($index)
{
    $letter = '';
    $index++;
    while ($index > 0) {
        $mod = ($index - 1) % 26;
        $letter = chr(65 + $mod) . $letter;
        $index = (int) (($index - $mod) / 26);
    }
    return $letter;
}

function xlsx_col_index($ref)
{
    // ref contoh: "C5" -> ambil huruf kolom "C" -> index 2
    preg_match('/^[A-Z]+/', $ref, $m);
    $letters = $m[0] ?? 'A';
    $index = 0;
    foreach (str_split($letters) as $ch) {
        $index = $index * 26 + (ord($ch) - 64);
    }
    return $index - 1;
}

function xlsx_escape($text)
{
    $text = (string) $text;
    $text = str_replace(['&', '<', '>', '"', "'"], ['&amp;', '&lt;', '&gt;', '&quot;', '&apos;'], $text);
    // buang karakter kontrol yang tidak valid di XML
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $text);
}

/**
 * Membuat isi file .xlsx (1 sheet) dari array baris.
 * $rows = [ ['Header1','Header2',...], ['val1','val2',...], ... ]
 * Mengembalikan path file sementara berisi konten .xlsx, atau
 * false jika ZipArchive tidak tersedia di server.
 */
function xlsx_write(array $rows, $sheetName = 'Data')
{
    if (!xlsx_supported()) {
        return false;
    }

    $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_');
    @unlink($tmpFile);
    $tmpFile .= '.xlsx';

    $zip = new ZipArchive();
    $zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    $zip->addEmptyDir('_rels');
    $zip->addEmptyDir('xl');
    $zip->addEmptyDir('xl/_rels');
    $zip->addEmptyDir('xl/worksheets');

    $zip->addFromString('[Content_Types].xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
        '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
        '<Default Extension="xml" ContentType="application/xml"/>' .
        '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
        '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
        '</Types>');

    $zip->addFromString('_rels/.rels',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
        '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
        '</Relationships>');

    $zip->addFromString('xl/workbook.xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
        '<sheets><sheet name="' . xlsx_escape($sheetName) . '" sheetId="1" r:id="rId1"/></sheets>' .
        '</workbook>');

    $zip->addFromString('xl/_rels/workbook.xml.rels',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
        '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
        '</Relationships>');

    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $sheetXml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

    foreach ($rows as $rIndex => $row) {
        $r = $rIndex + 1;
        $sheetXml .= '<row r="' . $r . '">';
        foreach (array_values($row) as $cIndex => $value) {
            $ref = xlsx_col_letter($cIndex) . $r;
            $sheetXml .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . xlsx_escape($value) . '</t></is></c>';
        }
        $sheetXml .= '</row>';
    }

    $sheetXml .= '</sheetData></worksheet>';

    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
    $zip->close();

    return $tmpFile;
}

/**
 * Membaca sheet pertama dari file .xlsx menjadi array baris.
 * Setiap baris = array nilai text (index 0 = kolom A, dst),
 * sudah menghitung kolom kosong yang dilewati Excel.
 * Mendukung sel bertipe shared string (t="s"), inline string
 * (t="inlineStr"), maupun angka biasa/formula (default/"str").
 */
function xlsx_read($filePath)
{
    if (!xlsx_supported()) {
        throw new Exception('Ekstensi PHP ZipArchive/SimpleXML tidak tersedia di server ini.');
    }

    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        throw new Exception('File .xlsx tidak valid atau rusak.');
    }

    // Baca shared strings jika ada
    $sharedStrings = [];
    $sharedXmlRaw = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXmlRaw !== false) {
        $sharedXml = @simplexml_load_string($sharedXmlRaw);
        if ($sharedXml !== false) {
            foreach ($sharedXml->si as $si) {
                if (isset($si->t)) {
                    $sharedStrings[] = (string) $si->t;
                } else {
                    // rich text terpisah beberapa <r><t>
                    $text = '';
                    foreach ($si->r as $r) {
                        $text .= (string) $r->t;
                    }
                    $sharedStrings[] = $text;
                }
            }
        }
    }

    // Tentukan file sheet pertama lewat workbook.xml + rels
    $sheetPath = 'xl/worksheets/sheet1.xml';
    $workbookRaw = $zip->getFromName('xl/workbook.xml');
    $relsRaw = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if ($workbookRaw !== false && $relsRaw !== false) {
        $wb = @simplexml_load_string($workbookRaw);
        $rels = @simplexml_load_string($relsRaw);
        if ($wb !== false && $rels !== false && isset($wb->sheets->sheet[0])) {
            $wb->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $firstSheet = $wb->sheets->sheet[0];
            $attrs = $firstSheet->attributes('r', true);
            $rId = (string) $attrs['id'];
            foreach ($rels->Relationship as $rel) {
                if ((string) $rel['Id'] === $rId) {
                    $target = (string) $rel['Target'];
                    $sheetPath = 'xl/' . ltrim($target, '/');
                    break;
                }
            }
        }
    }

    $sheetRaw = $zip->getFromName($sheetPath);
    if ($sheetRaw === false) {
        $zip->close();
        throw new Exception('Tidak dapat membaca isi sheet pada file .xlsx.');
    }
    $sheetXml = @simplexml_load_string($sheetRaw);
    $zip->close();

    if ($sheetXml === false) {
        throw new Exception('Format sheet .xlsx tidak dikenali.');
    }

    $rows = [];
    foreach ($sheetXml->sheetData->row as $row) {
        $rowData = [];
        $maxCol = -1;
        $cells = [];
        foreach ($row->c as $c) {
            $ref = (string) $c['r'];
            $colIndex = $ref !== '' ? xlsx_col_index($ref) : (count($cells));
            $type = (string) $c['t'];

            if ($type === 's') {
                $sIndex = (int) $c->v;
                $value = $sharedStrings[$sIndex] ?? '';
            } elseif ($type === 'inlineStr') {
                $value = isset($c->is->t) ? (string) $c->is->t : '';
            } elseif ($type === 'str') {
                $value = (string) $c->v;
            } else {
                // numeric / kosong
                $value = isset($c->v) ? (string) $c->v : '';
            }

            $cells[$colIndex] = $value;
            $maxCol = max($maxCol, $colIndex);
        }
        for ($i = 0; $i <= $maxCol; $i++) {
            $rowData[] = $cells[$i] ?? '';
        }
        $rows[] = $rowData;
    }

    return $rows;
}

/**
 * Konversi nilai serial tanggal Excel (angka) menjadi format Y-m-d.
 * Dipakai untuk kolom Tanggal Lahir bila Excel menyimpannya sebagai
 * angka (karena user memformat sel sebagai Date, bukan Text).
 */
function xlsx_maybe_date($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    // Sudah berformat YYYY-MM-DD
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return $value;
    }
    // Format tanggal umum lain, contoh 17/05/2010 atau 17-05-2010
    if (preg_match('#^(\d{1,2})[/\-](\d{1,2})[/\-](\d{4})$#', $value, $m)) {
        return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }
    // Serial angka Excel (basis 1899-12-30)
    if (preg_match('/^\d+(\.\d+)?$/', $value)) {
        $serial = (float) $value;
        if ($serial > 20000 && $serial < 80000) { // rentang wajar tahun 1954-2119
            $unix = ($serial - 25569) * 86400;
            return gmdate('Y-m-d', (int) $unix);
        }
    }
    return $value; // biarkan apa adanya, akan divalidasi saat simpan
}
