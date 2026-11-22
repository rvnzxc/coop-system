<?php

namespace App\Services;

class XlsxWriter
{
    private const STYLE_DEFAULT = 0;

    private const STYLE_HEADER = 1;

    private const STYLE_BODY = 2;

    private const STYLE_CURRENCY = 3;

    private const STYLE_DATETIME = 4;

    private const STYLE_TOTAL = 5;

    private const STYLE_TOTAL_CURRENCY = 6;

    /**
     * Build a valid .xlsx workbook from a matrix of rows.
     *
     * Each cell may be a scalar or an array with any of these keys:
     * value, type (string, number, date, formula), style, formula, display.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array{column_widths?: array<int, float|int>, freeze_header?: bool}  $options
     */
    public static function fromRows(array $rows, string $sheetName = 'Sheet1', array $options = []): string
    {
        $options = array_merge([
            'column_widths' => [],
            'freeze_header' => false,
        ], $options);

        $files = [
            '[Content_Types].xml' => self::contentTypes(),
            '_rels/.rels' => self::rootRels(),
            'xl/workbook.xml' => self::workbook($sheetName),
            'xl/_rels/workbook.xml.rels' => self::workbookRels(),
            'xl/styles.xml' => self::styles(),
            'xl/worksheets/sheet1.xml' => self::worksheet($rows, $options),
        ];

        return self::zip($files);
    }

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private static function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private static function workbook(string $sheetName): string
    {
        $name = self::escape($sheetName !== '' ? $sheetName : 'Sheet1');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$name.'" sheetId="1" r:id="rId1"/></sheets>'
            .'<calcPr calcId="191029" fullCalcOnLoad="1"/>'
            .'</workbook>';
    }

    private static function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private static function styles(): string
    {
        $alignment = '<alignment vertical="top" wrapText="1"/>';
        $headerAlignment = '<alignment horizontal="center" vertical="center" wrapText="1"/>';
        $numberAlignment = '<alignment horizontal="right" vertical="top" wrapText="1"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="2">'
            .'<numFmt numFmtId="164" formatCode="'.self::escape('"'."\u{20B1}".'"#,##0.00').'"/>'
            .'<numFmt numFmtId="165" formatCode="'.self::escape('mmm dd, yyyy h:mm AM/PM').'"/>'
            .'</numFmts>'
            .'<fonts count="2">'
            .'<font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/><scheme val="minor"/></font>'
            .'<font><b/><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/><scheme val="minor"/></font>'
            .'</fonts>'
            .'<fills count="4">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFE2EFDA"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFC6E0B4"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="2">'
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border>'
            .'<left style="thin"><color rgb="FFB7B7B7"/></left>'
            .'<right style="thin"><color rgb="FFB7B7B7"/></right>'
            .'<top style="thin"><color rgb="FFB7B7B7"/></top>'
            .'<bottom style="thin"><color rgb="FFB7B7B7"/></bottom>'
            .'<diagonal/>'
            .'</border>'
            .'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="7">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'.$headerAlignment.'</xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1">'.$alignment.'</xf>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1">'.$numberAlignment.'</xf>'
            .'<xf numFmtId="165" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1">'.$alignment.'</xf>'
            .'<xf numFmtId="0" fontId="1" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'.$alignment.'</xf>'
            .'<xf numFmtId="164" fontId="1" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'.$numberAlignment.'</xf>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array{column_widths?: array<int, float|int>, freeze_header?: bool}  $options
     */
    private static function worksheet(array $rows, array $options): string
    {
        $sheetData = '';
        $columnCount = 0;
        $widths = array_values($options['column_widths'] ?? []);
        $rowCount = count($rows);

        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 1;
            $cells = '';
            $columnCount = max($columnCount, count($row));

            foreach (array_values($row) as $columnIndex => $value) {
                $cell = self::normalizeCell($value);
                $reference = self::columnLetter($columnIndex).$rowNumber;
                $style = self::styleId($cell, $rowIndex);
                $cells .= self::cellXml($reference, $cell, $style);
            }

            $height = self::rowHeight($row, $widths, $rowIndex);
            $sheetData .= '<row r="'.$rowNumber.'" ht="'.$height.'" customHeight="1">'.$cells.'</row>';
        }

        $dimension = 'A1:'.self::columnLetter(max(0, $columnCount - 1)).max(1, $rowCount);
        $freezeHeader = (bool) ($options['freeze_header'] ?? false);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<dimension ref="'.$dimension.'"/>'
            .self::sheetViews($freezeHeader)
            .'<sheetFormatPr defaultRowHeight="18"/>'
            .self::columns($columnCount, $widths)
            .'<sheetData>'.$sheetData.'</sheetData>'
            .'<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>'
            .'</worksheet>';
    }

    private static function sheetViews(bool $freezeHeader): string
    {
        if (! $freezeHeader) {
            return '<sheetViews><sheetView workbookViewId="0"/></sheetViews>';
        }

        return '<sheetViews><sheetView workbookViewId="0">'
            .'<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>'
            .'<selection pane="bottomLeft" activeCell="A2" sqref="A2"/>'
            .'</sheetView></sheetViews>';
    }

    /**
     * @param  array<int, float|int>  $widths
     */
    private static function columns(int $columnCount, array $widths): string
    {
        if ($columnCount === 0) {
            return '';
        }

        $columns = '<cols>';

        for ($index = 0; $index < $columnCount; $index++) {
            $min = $index + 1;
            $width = $widths[$index] ?? 20;

            $columns .= '<col min="'.$min.'" max="'.$min.'" width="'.$width.'" customWidth="1"/>';
        }

        return $columns.'</cols>';
    }

    /**
     * @return array{value: mixed, type: string, style: string, formula?: string, display?: string}
     */
    private static function normalizeCell(mixed $value): array
    {
        if (is_array($value)) {
            return array_merge([
                'value' => '',
                'type' => 'string',
                'style' => 'body',
            ], $value);
        }

        return [
            'value' => $value,
            'type' => is_int($value) || is_float($value) ? 'number' : 'string',
            'style' => 'body',
        ];
    }

    /**
     * @param  array<string, mixed>  $cell
     */
    private static function styleId(array $cell, int $rowIndex): int
    {
        if ($rowIndex === 0) {
            return self::STYLE_HEADER;
        }

        return match ($cell['style'] ?? 'body') {
            'header' => self::STYLE_HEADER,
            'currency' => self::STYLE_CURRENCY,
            'datetime' => self::STYLE_DATETIME,
            'total' => self::STYLE_TOTAL,
            'total_currency' => self::STYLE_TOTAL_CURRENCY,
            default => self::STYLE_BODY,
        };
    }

    /**
     * @param  array<string, mixed>  $cell
     */
    private static function cellXml(string $reference, array $cell, int $style): string
    {
        $attributes = ' r="'.$reference.'" s="'.$style.'"';
        $type = $cell['type'] ?? 'string';
        $value = $cell['value'] ?? '';

        if ($type === 'formula') {
            return '<c'.$attributes.'><f>'.self::escape((string) ($cell['formula'] ?? '')).'</f>'
                .'<v>'.self::numericValue($value).'</v></c>';
        }

        if ($type === 'number' || $type === 'date') {
            return '<c'.$attributes.'><v>'.self::numericValue($value).'</v></c>';
        }

        return '<c'.$attributes.' t="inlineStr"><is><t xml:space="preserve">'
            .self::escape((string) $value)
            .'</t></is></c>';
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<int, float|int>  $widths
     */
    private static function rowHeight(array $row, array $widths, int $rowIndex): float
    {
        if ($rowIndex === 0) {
            return 30;
        }

        $lines = 1;

        foreach (array_values($row) as $columnIndex => $value) {
            $cell = self::normalizeCell($value);
            $text = (string) ($cell['display'] ?? $cell['value'] ?? '');

            if ($text === '') {
                continue;
            }

            $width = (float) ($widths[$columnIndex] ?? 20);
            $charactersPerLine = max(8, (int) floor($width * 1.05));
            $cellLines = 0;

            foreach (preg_split('/\R/u', $text) ?: [$text] as $line) {
                $cellLines += max(1, (int) ceil(mb_strlen($line) / $charactersPerLine));
            }

            $lines = max($lines, $cellLines);
        }

        return max(18, min(409, $lines * 15));
    }

    private static function numericValue(mixed $value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        $number = (float) $value;

        if (! is_finite($number)) {
            return '0';
        }

        $formatted = rtrim(rtrim(number_format($number, 10, '.', ''), '0'), '.');

        return $formatted === '' || $formatted === '-' ? '0' : $formatted;
    }

    private static function columnLetter(int $index): string
    {
        $letters = '';

        for ($index++; $index > 0; $index = intdiv($index - 1, 26)) {
            $letters = chr(65 + (($index - 1) % 26)).$letters;
        }

        return $letters;
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * Assemble a ZIP archive using the "stored" (uncompressed) method so no
     * ext-zip / ZipArchive dependency is required.
     *
     * @param  array<string, string>  $files
     */
    private static function zip(array $files): string
    {
        $now = getdate();
        $dosTime = ($now['hours'] << 11) | ($now['minutes'] << 5) | ($now['seconds'] >> 1);
        $dosDate = (($now['year'] - 1980) << 9) | ($now['mon'] << 5) | $now['mday'];

        $local = '';
        $central = '';
        $offset = 0;

        foreach ($files as $name => $data) {
            $crc = crc32($data);
            $size = strlen($data);
            $nameLength = strlen($name);

            $localHeader = pack('V', 0x04034B50)
                .pack('v', 20)
                .pack('v', 0)
                .pack('v', 0)
                .pack('v', $dosTime)
                .pack('v', $dosDate)
                .pack('V', $crc)
                .pack('V', $size)
                .pack('V', $size)
                .pack('v', $nameLength)
                .pack('v', 0)
                .$name
                .$data;

            $centralHeader = pack('V', 0x02014B50)
                .pack('v', 20)
                .pack('v', 20)
                .pack('v', 0)
                .pack('v', 0)
                .pack('v', $dosTime)
                .pack('v', $dosDate)
                .pack('V', $crc)
                .pack('V', $size)
                .pack('V', $size)
                .pack('v', $nameLength)
                .pack('v', 0)
                .pack('v', 0)
                .pack('v', 0)
                .pack('v', 0)
                .pack('V', 0)
                .pack('V', $offset)
                .$name;

            $local .= $localHeader;
            $central .= $centralHeader;
            $offset += strlen($localHeader);
        }

        $endOfCentralDirectory = pack('V', 0x06054B50)
            .pack('v', 0)
            .pack('v', 0)
            .pack('v', count($files))
            .pack('v', count($files))
            .pack('V', strlen($central))
            .pack('V', $offset)
            .pack('v', 0);

        return $local.$central.$endOfCentralDirectory;
    }
}
