<?php

namespace Tests\Unit;

use App\Support\ReportePdfSimple;
use PHPUnit\Framework\TestCase;

class ReportePdfTabularTest extends TestCase
{
    public function test_textos_acentuados_no_generan_error_iconv(): void
    {
        $pdf = new ReportePdfSimple();
        $pdf->titulo('Ventas por ubicación — FIPAZ');
        $pdf->texto('Comisión del vendedor: José Muñoz');
        $pdf->tabla(['Producto', 'Presentación', 'Total'], [
            ['TRICEMAC - Cereal de quinua', 'Caja 400 g', 'Bs 160,00'],
            ['Granola de maní y plátano', 'Presentación clásica', 'Bs 75,00'],
            ['Lote inválido ' . "\xFF" . ' — revisión', 'Bolsa', 'Bs 5,00'],
        ], [200, 180, 120]);
        $bytes = $pdf->generar();
        self::assertStringStartsWith('%PDF-', $bytes);
        self::assertStringContainsString('TRICEMAC', $bytes);
        self::assertStringContainsString('Granola de man', $bytes);
    }

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
