<?php

namespace App\Services;

use Picqer\Barcode\Renderers\SvgRenderer;
use Picqer\Barcode\Types\TypeCode128;

class BarcodeService
{
    /**
     * Render a scannable Code 128 barcode as inline SVG.
     *
     * Code 128 encodes the full printable ASCII range, so it covers both the
     * internal P###### codes and member numbers such as MEM00001 or #00001.
     *
     * @param  string  $code  Value to encode.
     * @param  int  $width  Rendered width in pixels.
     * @param  bool  $showText  Print the human-readable code under the bars.
     * @return string SVG markup, ready to inline into a Blade template.
     */
    public static function generateBarcode($code, $width = 200, $showText = true)
    {
        $code = (string) $code;

        if (trim($code) === '') {
            return '';
        }

        $barsHeight = $showText ? 60 : 40;
        $totalHeight = $showText ? $barsHeight + 16 : $barsHeight;

        $type = new TypeCode128;

        $renderer = (new SvgRenderer)
            ->setSvgType(SvgRenderer::TYPE_SVG_INLINE)
            ->setBackgroundColor([255, 255, 255]);

        $svg = $renderer->render($type->getBarcode($code), $width, $barsHeight);

        if ($showText) {
            // Room has to be made under the bars for the label; the renderer
            // only draws the bars, so both the height and the viewBox that
            // scales them have to grow together.
            $svg = str_replace(
                [
                    'height="'.$barsHeight.'"',
                    'viewBox="0 0 '.$width.' '.$barsHeight.'"',
                    '</svg>',
                ],
                [
                    'height="'.$totalHeight.'"',
                    'viewBox="0 0 '.$width.' '.$totalHeight.'"',
                    '<text x="'.($width / 2).'" y="'.($barsHeight + 11).'" text-anchor="middle" '
                        .'font-family="monospace" font-size="10" fill="black">'
                        .htmlspecialchars($code, ENT_QUOTES).'</text>'.PHP_EOL.'</svg>',
                ],
                $svg
            );
        }

        return $svg;
    }

    /**
     * Expand a scanned value into the codes worth trying against the database.
     *
     * Handheld scanners are inconsistent about what they send: some append a
     * GS1 application identifier after the GTIN, some leave surrounding
     * whitespace or control characters, and a product can legitimately be
     * printed as either the 12 digit UPC-A or the 13 digit EAN-13 form. All of
     * those have to resolve to the same item.
     *
     * @param  string|null  $code  Raw value coming off a scanner.
     * @return string[] Candidate values, most specific first.
     */
    public static function lookupCandidates($code): array
    {
        if ($code === null) {
            return [];
        }

        // Scanners sometimes terminate with CR/LF or embed GS (0x1D) separators.
        $raw = trim((string) preg_replace('/[\x00-\x1F\x7F]/', '', (string) $code));

        if ($raw === '') {
            return [];
        }

        $candidates = [];
        $push = function (?string $value) use (&$candidates): void {
            if ($value !== null && $value !== '' && ! in_array($value, $candidates, true)) {
                $candidates[] = $value;
            }
        };

        $push($raw);

        // Only UPC/EAN style codes get synonym treatment; internal codes such
        // as P123456 or member numbers are matched exactly as scanned.
        $digits = preg_replace('/\D+/', '', $raw);
        $length = strlen($digits);

        if ($length < 8) {
            return $candidates;
        }

        // Anything longer than 13 digits is a GTIN with application-identifier
        // data trailing it, so the GTIN itself is the leading 13 digits.
        $gtin = $length > 13 ? substr($digits, 0, 13) : $digits;
        $push($gtin);

        if (strlen($gtin) === 12) {
            $push('0'.$gtin);            // UPC-A rendered as EAN-13
        } elseif (strlen($gtin) === 13 && $gtin[0] === '0') {
            $push(substr($gtin, 1));       // EAN-13 rendered as UPC-A
        }

        if ($length > 13) {
            // A GS1 scan whose GTIN is the 12 digit form still has to resolve.
            $push(substr($digits, 0, 12));
        }

        return $candidates;
    }
}
