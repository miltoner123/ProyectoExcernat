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
        $bytes = $this->aBytes($texto);
        $bytes = str_replace(["\\", '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ' '], $bytes);
        // Truncar por ancho para mantener legibilidad en A4.
        if (strlen($bytes) > 105) $bytes = substr($bytes, 0, 102) . '...';
        $font = $bold ? '/F2' : '/F1';
        $this->lineas[] = 'BT '.$font.' '.$size.' Tf 43 '.$this->y.' Td ('.$bytes.') Tj ET';
        $this->y -= ($size >= 15 ? 25 : 15);
    }

    /** Divide textos sin truncar palabras, sin depender de extensiones opcionales. */
    public function textoPartido(string $texto): void
    {
        $palabras = preg_split('/\s+/u', trim($texto)) ?: [];
        $linea = '';
        foreach ($palabras as $palabra) {
            if ($linea !== '' && strlen($linea.' '.$palabra) > 85) {
                $this->texto($linea);
                $linea = $palabra;
            } else {
                $linea = $linea === '' ? $palabra : $linea.' '.$palabra;
            }
        }
        if ($linea !== '') $this->texto($linea);
    }

    /** Tabla A4 con ajuste de texto, saltos de página y cabeceras repetidas. */
    public function tabla(array $cabeceras, array $filas, array $anchos): void
    {
        if (count($cabeceras) !== count($anchos) || array_sum($anchos) > 510) {
            throw new \InvalidArgumentException('Columnas PDF incompatibles con A4.');
        }
        $n = count($cabeceras);
        $cabecera = function () use ($cabeceras, $anchos, $n) {
            if ($this->y < 92) $this->paginaSiguiente();
            $this->lineas[] = '0.06 0.22 0.16 rg 43 '.($this->y-17).' '.array_sum($anchos).' 21 re f';
            $x=47;
            for ($i=0;$i<$n;$i++) {
                $this->textoPosicion((string)$cabeceras[$i], $x, $this->y-10, 8, true, true);
                $x += $anchos[$i];
            }
            $this->y -= 26;
        };
        $cabecera();
        if (empty($filas)) $filas=[array_pad(['Sin registros'], $n, '')];
        foreach ($filas as $fila) {
            $lineas=[];
            foreach ($anchos as $i=>$ancho) {
                $bytes=$this->aBytes((string)($fila[$i] ?? ''));
                $max=max(5,(int)floor(($ancho-16)/5.5));
                $lineas[$i]=str_split($bytes,$max);
            }
            $alto=max(1,max(array_map('count',$lineas)))*11+8;
            if ($alto > 645) { // Protege contra filas individuales extremadamente largas.
                foreach ($lineas as &$partes) $partes=array_slice($partes,0,50);
                unset($partes);
                $alto=max(1,max(array_map('count',$lineas)))*11+8;
            }
            if ($this->y-$alto < 65) { $this->paginaSiguiente();$cabecera(); }
            $x=47;
            for ($i=0;$i<$n;$i++) {
                foreach ($lineas[$i] as $j=>$linea) $this->textoPosicion($linea,$x,$this->y-10-$j*11,8,false,false,true);
                $x += $anchos[$i];
            }
            $this->lineas[]='0.85 0.89 0.86 RG 43 '.($this->y-$alto).' '.array_sum($anchos).' 0 re S';
            $this->y -= $alto;
        }
        $this->y -= 10;
    }

    private function aBytes(string $valor): string
    {
        // Una sola conversion UTF-8 -> WinAnsi por texto; sin advertencias por datos legados.
        $out = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $valor);
        return $out === false ? preg_replace('/[^\x20-\x7E]/', '?', $valor) : $out;
    }

    private function textoPosicion(string $texto, int $x, int $y, int $size=8, bool $negrita=false, bool $blanco=false, bool $yaConvertido=false): void
    {
        $bytes=$yaConvertido ? $texto : $this->aBytes($texto);
        $bytes=str_replace(['\\','(',')',"\r","\n"],['\\\\','\\(','\\)','', ' '],$bytes);
        $color=$blanco?'1 1 1 rg':'0.07 0.2 0.15 rg';
        $this->lineas[]=$color.' BT '.($negrita?'/F2':'/F1').' '.$size.' Tf '.$x.' '.$y.' Td ('.$bytes.') Tj ET';
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
