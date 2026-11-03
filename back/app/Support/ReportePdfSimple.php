<?php

namespace App\Support;

/** PDF A4 tabular ligero; no escribe archivos ni necesita paquetes externos. */
final class ReportePdfSimple
{
    private array $paginas = [];
    private array $lineas = [];
    private int $y = 795;

    public function titulo(string $texto): void
    {
        $this->texto($texto, 16, true);
        $this->separador();
    }

    public function separador(): void
    {
        $this->y -= 7;
    }

    public function texto(string $texto, int $size = 10, bool $bold = false): void
    {
        if ($this->y < 65) $this->paginaSiguiente();
        $bytes = iconv('UTF-8', 'Windows-1252//TRANSLIT', $texto);
        if ($bytes === false) $bytes = $texto;
        $bytes = str_replace(["\\", '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ' '], $bytes);
        // Truncar por ancho para mantener legibilidad en A4.
        if (strlen($bytes) > 105) $bytes = substr($bytes, 0, 102) . '...';
        $font = $bold ? '/F2' : '/F1';
        $this->lineas[] = 'BT '.$font.' '.$size.' Tf 43 '.$this->y.' Td ('.$bytes.') Tj ET';
        $this->y -= ($size >= 15 ? 25 : 15);
    }

    private function paginaSiguiente(): void
    {
        $this->paginas[] = implode("\n", $this->lineas);
        $this->lineas = [];
        $this->y = 795;
    }

    public function generar(): string
    {
        $this->paginaSiguiente();
        $total = count($this->paginas);
        $objects = [];
        // Referencias fijas: 1=Catálogo, 2=Páginas, 3=Normal, 4=Negrita.
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $kids = [];
        foreach ($this->paginas as $index => $body) {
            $pageId = 5 + $index * 2;
            $streamId = $pageId + 1;
            $kids[] = $pageId.' 0 R';
            $footer = 'BT /F1 9 Tf 43 32 Td (EXCERNAT | Pagina '.($index+1).' de '.$total.') Tj ET';
            $stream = $body."\n".$footer;
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '.$streamId.' 0 R >>';
            $objects[$streamId] = '<< /Length '.strlen($stream).' >>'."\nstream\n".$stream."\nendstream";
        }
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.$total.' >>';
        ksort($objects);
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $id => $obj) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "$id 0 obj\n$obj\nendobj\n";
        }
        $start = strlen($pdf);
        $pdf .= "xref\n0 ".(max(array_keys($objects))+1)."\n0000000000 65535 f \n";
        for ($i=1; $i<=max(array_keys($objects)); $i++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$i] ?? 0)."\n";
        }
        $pdf .= 'trailer << /Size '.(max(array_keys($objects))+1).' /Root 1 0 R >>'."\nstartxref\n$start\n%%EOF\n";
        return $pdf;
    }
}
