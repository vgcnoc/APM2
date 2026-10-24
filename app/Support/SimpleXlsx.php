<?php

namespace App\Support;

class SimpleXlsx
{
    /**
     * Create an XLSX file at $outputPath with specified headers and rows.
     *
     * @param array $headers Array of string headers
     * @param array $rows Array of row arrays
     * @param string $outputPath Path to save the .xlsx file
     * @return bool
     */
    public static function create(array $headers, array $rows, string $outputPath): bool
    {
        if (!class_exists('ZipArchive')) {
            return false;
        }

        $zip = new \ZipArchive();
        if ($zip->open($outputPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        // 1. [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // 2. _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        // 3. xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // 4. xl/workbook.xml
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        // 5. xl/styles.xml (styled with light header styling)
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border>'
            . '<left style="thin"><color rgb="FFCBD5E1"/></left>'
            . '<right style="thin"><color rgb="FFCBD5E1"/></right>'
            . '<top style="thin"><color rgb="FFCBD5E1"/></top>'
            . '<bottom style="thin"><color rgb="FFCBD5E1"/></bottom>'
            . '<diagonal/>'
            . '</border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'
            . '</cellXfs>'
            . '</styleSheet>';
        $zip->addFromString('xl/styles.xml', $styles);

        // 6. xl/worksheets/sheet1.xml
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>';

        $colLetter = function ($index) {
            $letter = '';
            while ($index >= 0) {
                $letter = chr($index % 26 + 65) . $letter;
                $index = intval($index / 26) - 1;
            }
            return $letter;
        };

        // Header row (r=1, s=1 for bold & light gray background)
        $sheetXml .= '<row r="1">';
        foreach ($headers as $colIdx => $headerVal) {
            $cellRef = $colLetter($colIdx) . '1';
            $escaped = htmlspecialchars((string)$headerVal, ENT_XML1, 'UTF-8');
            $sheetXml .= '<c r="' . $cellRef . '" t="inlineStr" s="1"><is><t>' . $escaped . '</t></is></c>';
        }
        $sheetXml .= '</row>';

        // Data rows (r >= 2, s=0)
        $rowNum = 2;
        foreach ($rows as $row) {
            $sheetXml .= '<row r="' . $rowNum . '">';
            $colIdx = 0;
            foreach ($row as $val) {
                $cellRef = $colLetter($colIdx) . $rowNum;
                $valStr = (string)$val;
                // Preserve numbers without leading zeros as numeric cells
                if (is_numeric($val) && !preg_match('/^0[0-9]+/', $valStr)) {
                    $sheetXml .= '<c r="' . $cellRef . '" s="0"><v>' . $valStr . '</v></c>';
                } else {
                    $escaped = htmlspecialchars($valStr, ENT_XML1, 'UTF-8');
                    $sheetXml .= '<c r="' . $cellRef . '" t="inlineStr" s="0"><is><t>' . $escaped . '</t></is></c>';
                }
                $colIdx++;
            }
            $sheetXml .= '</row>';
            $rowNum++;
        }

        $sheetXml .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);

        return $zip->close();
    }

    /**
     * Read an XLSX, XLS, or CSV file and return array of rows (each row as array of column values).
     *
     * @param string $filePath
     * @return array
     */
    public static function read(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        // Check if file starts with ZIP magic header 'PK' (0x50 0x4B)
        $f = fopen($filePath, 'rb');
        $magic = fread($f, 2);
        fclose($f);

        if ($magic === "PK" && class_exists('ZipArchive')) {
            $xlsxRows = self::readXlsx($filePath);
            if (!empty($xlsxRows)) {
                return $xlsxRows;
            }
        }

        // Fallback: Read as CSV / text / Excel HTML table
        return self::readFallback($filePath);
    }

    /**
     * Read real XLSX from zip package.
     */
    protected static function readXlsx(string $filePath): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            return [];
        }

        // 1. Read shared strings
        $sharedStrings = [];
        $ssIndex = $zip->locateName('xl/sharedStrings.xml', \ZipArchive::FL_NODIR);
        if ($ssIndex !== false) {
            $ssXml = $zip->getFromIndex($ssIndex);
            if ($ssXml) {
                $xml = @simplexml_load_string($ssXml);
                if ($xml && isset($xml->si)) {
                    foreach ($xml->si as $si) {
                        if (isset($si->t)) {
                            $sharedStrings[] = (string)$si->t;
                        } elseif (isset($si->r)) {
                            $t = '';
                            foreach ($si->r as $r) {
                                $t .= (string)$r->t;
                            }
                            $sharedStrings[] = $t;
                        } else {
                            $sharedStrings[] = '';
                        }
                    }
                }
            }
        }

        // 2. Locate first worksheet
        $sheetIndex = $zip->locateName('xl/worksheets/sheet1.xml', \ZipArchive::FL_NODIR);
        if ($sheetIndex === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (strpos($name, 'xl/worksheets/sheet') !== false && substr($name, -4) === '.xml') {
                    $sheetIndex = $i;
                    break;
                }
            }
        }

        if ($sheetIndex === false) {
            $zip->close();
            return [];
        }

        $sheetXml = $zip->getFromIndex($sheetIndex);
        $zip->close();

        if (!$sheetXml) {
            return [];
        }

        $xml = @simplexml_load_string($sheetXml);
        if (!$xml || !isset($xml->sheetData->row)) {
            return [];
        }

        $colRefToIndex = function ($ref) {
            $letters = preg_replace('/[0-9]/', '', $ref);
            $idx = 0;
            $len = strlen($letters);
            for ($i = 0; $i < $len; $i++) {
                $idx = $idx * 26 + (ord(strtoupper($letters[$i])) - 64);
            }
            return $idx - 1;
        };

        $rows = [];
        foreach ($xml->sheetData->row as $r) {
            $rowValues = [];
            foreach ($r->c as $c) {
                $ref = (string)$c['r'];
                $colIdx = $colRefToIndex($ref);
                $type = (string)$c['t'];

                $val = '';
                if ($type === 's') {
                    $ssId = (int)$c->v;
                    $val = $sharedStrings[$ssId] ?? '';
                } elseif ($type === 'inlineStr') {
                    $val = (string)($c->is->t ?? '');
                } elseif ($type === 'str') {
                    $val = (string)($c->v ?? '');
                } else {
                    $val = (string)($c->v ?? '');
                }

                $rowValues[$colIdx] = $val;
            }

            if (!empty($rowValues)) {
                $maxCol = max(array_keys($rowValues));
                $fullRow = [];
                for ($ci = 0; $ci <= $maxCol; $ci++) {
                    $fullRow[$ci] = $rowValues[$ci] ?? '';
                }
                $rows[] = $fullRow;
            }
        }

        return $rows;
    }

    /**
     * Fallback for CSV / TSV / HTML-based XLS.
     */
    protected static function readFallback(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if (!$content) {
            return [];
        }

        // If HTML table (often generated by older Excel export)
        if (stripos($content, '<table') !== false && stripos($content, '<tr') !== false) {
            $rows = [];
            if (preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $content, $trMatches)) {
                foreach ($trMatches[1] as $tr) {
                    if (preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $tr, $tdMatches)) {
                        $row = array_map(function ($val) {
                            return trim(strip_tags(html_entity_decode($val, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                        }, $tdMatches[1]);
                        if (!empty(array_filter($row, fn($x) => $x !== ''))) {
                            $rows[] = $row;
                        }
                    }
                }
            }
            if (!empty($rows)) {
                return $rows;
            }
        }

        // Strip BOM
        $bom = pack('H*', 'EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);

        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $content));
        if (empty($lines)) return [];

        // Check sep= directive
        if (preg_match('/^sep=([;,|\t])/i', trim($lines[0]), $m)) {
            $delimiter = $m[1];
            array_shift($lines);
        } else {
            $first = $lines[0] ?? '';
            $commaCount = substr_count($first, ',');
            $semiCount = substr_count($first, ';');
            $tabCount = substr_count($first, "\t");

            if ($tabCount > $commaCount && $tabCount > $semiCount) {
                $delimiter = "\t";
            } elseif ($semiCount > $commaCount) {
                $delimiter = ';';
            } else {
                $delimiter = ',';
            }
        }

        $rows = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') continue;
            $row = str_getcsv($trimmed, $delimiter);
            $rows[] = $row;
        }

        return $rows;
    }
}
