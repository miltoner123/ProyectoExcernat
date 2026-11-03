<?php

namespace Tests\Unit;

use App\Support\ReportePdfSimple;
use PHPUnit\Framework\TestCase;

class ReportePdfTabularTest extends TestCase
{
    public function test_genera_pdf_con_tabla_y_paginacion(): void
    {
        $pdf = new ReportePdfSimple();
        $pdf->titulo('EXCERNAT - INFORME OPERATIVO');
        $filas = [];
        for ($i = 0; $i < 140; $i++) {
            // Un identificador corto en la ultima fila permite verificar
            // el contenido aun cuando una celda larga se divida en lineas.
            $producto = $i === 139 ? 'Producto 139' : 'Producto de prueba '.$i;
            $filas[] = [$producto, 'Caja 400g - Lote '.$i, '2', '40.00', '80.00'];
        }
        $pdf->tabla(['Producto', 'Presentacion', 'Cantidad', 'Precio', 'Total'],
            $filas, [130, 160, 70, 70, 79]);
        $bytes = $pdf->generar();
        self::assertStringStartsWith('%PDF-', $bytes);
        self::assertStringContainsString('EXCERNAT', $bytes);
        self::assertStringContainsString('Pagina 2 de ', $bytes);
        self::assertStringContainsString('Producto 139', $bytes);
    }
}
