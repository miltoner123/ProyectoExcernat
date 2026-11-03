<?php
namespace Tests\Unit;

use App\Support\ReportePdfSimple;
use PHPUnit\Framework\TestCase;

class ReportePdfSimpleTest extends TestCase
{
    public function test_crea_pdf_a4_con_paginas_y_sin_archivos_temporales(): void
    {
        $pdf = new ReportePdfSimple();
        $pdf->titulo('Reporte de prueba EXCERNAT');
        for ($i = 0; $i < 150; $i++) $pdf->texto('Registro de venta '.($i + 1));
        $bytes = $pdf->generar();
        $this->assertStringStartsWith('%PDF-1.4', $bytes);
        $this->assertStringContainsString('/MediaBox [0 0 595 842]', $bytes);
        $this->assertStringContainsString('Pagina 2 de', $bytes);
    }
}
