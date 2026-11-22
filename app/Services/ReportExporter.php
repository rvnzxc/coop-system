<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ReportExporter
{
    public function csv(array $definition, string $filename): Response
    {
        return response()->stream(function () use ($definition) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $definition['columns']);

            $emit = function (array $row) use ($handle, $definition) {
                fputcsv($handle, $this->csvCells($row['cells'], $definition['currencyColumns'] ?? []));
            };

            ($definition['writeRows'])($emit);

            if (array_key_exists('totals', $definition)) {
                $emit(['cells' => $definition['totals']]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.csv"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function pdf(array $definition, string $filename): Response
    {
        if (! class_exists(Pdf::class)) {
            throw new \RuntimeException('The PDF library is not installed. Run: composer require barryvdh/laravel-dompdf');
        }

        $rows = [];
        ($definition['writeRows'])(function (array $row) use (&$rows) {
            $rows[] = $row;
        });

        $sections = [];
        foreach ($definition['sections'] ?? [] as $section) {
            $sectionRows = [];
            ($section['writeRows'])(function (array $row) use (&$sectionRows) {
                $sectionRows[] = $row;
            });
            $sections[] = array_merge($section, ['rows' => $sectionRows]);
        }

        $pdf = Pdf::loadView('reports.pdf', [
            'meta' => $definition['meta'],
            'summary' => $definition['summary'] ?? [],
            'note' => $definition['note'] ?? '',
            'columns' => $definition['columns'],
            'currencyColumns' => $definition['currencyColumns'] ?? [],
            'rows' => $rows,
            'totals' => $definition['totals'] ?? [],
            'sections' => $sections,
        ])
            ->setPaper('a4', 'landscape')
            ->setOption('defaultFont', 'DejaVu Sans');

        $pdf->render();

        $canvas = $pdf->getDomPDF()->getCanvas();
        $font = $pdf->getDomPDF()->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas->page_text(
            28,
            $canvas->get_height() - 14,
            'CCFMPC · '.$definition['meta']['title'].' · Page {PAGE_NUM} of {PAGE_COUNT}',
            $font,
            8,
            [0.25, 0.45, 0.32]
        );

        $output = $pdf->output();

        return new \Illuminate\Http\Response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.pdf"',
            'Content-Length' => (string) strlen($output),
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function csvCells(array $cells, array $currencyColumns): array
    {
        foreach ($currencyColumns as $index) {
            if (isset($cells[$index]) && is_numeric($cells[$index])) {
                $cells[$index] = number_format((float) $cells[$index], 2, '.', '');
            }
        }

        return $cells;
    }
}
