<?php

namespace App\Services;

use Mpdf\Mpdf;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class PdfService
{
    public function generatePdf($data, $html, $filename = 'document.pdf', $IorD = 'I', $pdfType = null)
    {
        if (Auth::check()) {
            $globalData = User::with('company')->find(Auth::id());
        } else {
            $globalData = User::with('company')->where('user_type', 'admin')->first();
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font_size' => 12,
            'default_font' => 'arial',  // your font key, must match config below
            'fontDir' => [
                resource_path('fonts/myfonts'),
                __DIR__ . '/../../../../vendor/mpdf/mpdf/ttfonts', // optional fallback
            ],
            'fontdata' => [
                'arial' => [
                    'R' => 'arial/Arial.ttf',
                    'B' => 'arial/Arial-bold.ttf',  // optional
                    'I' => 'arial/ArialCEItalic.ttf', // optional
                ],
                'ipaexg' => [
                    'R' => 'ipaexg-jp/ipaexg-jp.ttf',
                ],
                'dejavusans' => [
                    'R' => 'dejavu-sans/DejaVuSans.ttf',
                ],
                'Noto Sans Symbols' => [
                    'R' => 'noto-sans-symbols/NotoSansSymbols-VariableFont_wght.ttf',
                ],
                'Symbola' => [
                    'R' => 'symbola/Symbola.ttf',
                ],
            ],
            // 'autoScriptToLang' => true,
            // 'autoLangToFont' => true,
        ]);

        $mpdf->WriteHTML($html);
        if($pdfType == 'invoice'){
            $mpdf->SetHTMLFooter('<div style="text-align: center;">' . ($globalData->company->company_invoice_id ?? '') . '</div>', 'O'); // O = only last page
        }

        // 'I' for inline view, 'D' for download
        return $mpdf->Output($filename, $IorD);
    }
}
