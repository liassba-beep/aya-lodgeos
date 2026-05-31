<?php

namespace App\Support;

class SimplePdf
{
    public static function make(array $lines): string
    {
        $content = "BT\n/F1 12 Tf\n50 790 Td\n14 TL\n";

        foreach ($lines as $line) {
            $content .= '('.self::escape($line).") Tj\nT*\n";
        }

        $content .= "ET\n";

        return self::render($content);
    }

    public static function invoice(array $invoice): string
    {
        $content = '';
        $content .= self::rect(0, 792, 595, 50, 0.06, 0.06, 0.07);
        $content .= self::rect(0, 735, 595, 57, 0.98, 0.72, 0.14);
        $content .= self::text(48, 805, 22, 'FATURA', 'F2', 1, 1, 1);
        $content .= self::text(48, 764, 11, 'Documento fiscal emitido por AYA LodgeOS', 'F2', 0.08, 0.08, 0.09);
        $content .= self::text(430, 805, 12, 'N. '.$invoice['number'], 'F2', 1, 1, 1);
        $content .= self::text(430, 785, 9, 'Emissão: '.$invoice['issued_at'], 'F1', 1, 1, 1);
        $content .= self::text(430, 770, 9, 'Vencimento: '.$invoice['due_at'], 'F1', 1, 1, 1);

        $logoPath = $invoice['logo_path'] ?? null;
        $logoObject = self::jpegLogoObject($logoPath);

        if ($logoObject) {
            $content .= "q\n70 0 0 70 48 650 cm\n/Logo Do\nQ\n";
        } else {
            $content .= self::rect(48, 650, 70, 70, 0.94, 0.94, 0.94);
            $content .= self::text(62, 684, 16, 'LOGO', 'F2', 0.2, 0.2, 0.2);
        }

        $content .= self::text(135, 700, 16, $invoice['issuer_name'], 'F2');
        $content .= self::text(135, 682, 9, 'NUIT: '.$invoice['issuer_nuit'], 'F1', 0.25, 0.25, 0.25);
        $content .= self::text(135, 668, 9, $invoice['issuer_address'], 'F1', 0.25, 0.25, 0.25);
        $content .= self::text(135, 654, 9, $invoice['issuer_contacts'], 'F1', 0.25, 0.25, 0.25);

        $content .= self::rect(48, 570, 230, 55, 0.96, 0.96, 0.97);
        $content .= self::rect(318, 570, 230, 55, 0.96, 0.96, 0.97);
        $content .= self::text(64, 605, 10, 'CLIENTE', 'F2', 0.45, 0.45, 0.45);
        $content .= self::text(64, 588, 11, $invoice['client_name'], 'F2');
        $content .= self::text(64, 574, 9, 'NUIT: '.$invoice['client_nuit'].'  Contacto: '.$invoice['client_contact'], 'F1', 0.25, 0.25, 0.25);
        $content .= self::text(334, 605, 10, 'RESERVA', 'F2', 0.45, 0.45, 0.45);
        $content .= self::text(334, 588, 11, $invoice['reservation_code'].' - '.$invoice['room'], 'F2');
        $content .= self::text(334, 574, 9, $invoice['stay_dates'], 'F1', 0.25, 0.25, 0.25);

        $content .= self::rect(48, 525, 500, 28, 0.06, 0.06, 0.07);
        $content .= self::text(64, 535, 9, 'Descricao', 'F2', 1, 1, 1);
        $content .= self::text(360, 535, 9, 'IVA', 'F2', 1, 1, 1);
        $content .= self::text(450, 535, 9, 'Valor', 'F2', 1, 1, 1);

        $content .= self::rect(48, 487, 500, 38, 1, 1, 1);
        $content .= self::strokeRect(48, 487, 500, 38, 0.86, 0.86, 0.88);
        $content .= self::text(64, 508, 10, 'Alojamento e serviços da reserva '.$invoice['reservation_code'], 'F1');
        $content .= self::text(360, 508, 10, number_format((float) $invoice['tax_rate'], 2).'%');
        $content .= self::text(450, 508, 10, self::money($invoice['subtotal']), 'F2');

        $y = 410;
        foreach ([
            'Subtotal' => $invoice['subtotal'],
            'Desconto' => -1 * (float) $invoice['discount'],
            'IVA '.$invoice['tax_rate'].'%' => $invoice['tax'],
        ] as $label => $value) {
            $content .= self::text(350, $y, 10, $label, 'F1', 0.35, 0.35, 0.35);
            $content .= self::text(450, $y, 10, self::money($value), 'F2');
            $y -= 18;
        }

        $content .= self::rect(335, 335, 213, 42, 0.98, 0.72, 0.14);
        $content .= self::text(350, 354, 11, 'TOTAL', 'F2', 0.08, 0.08, 0.09);
        $content .= self::text(430, 354, 13, self::money($invoice['total']), 'F2', 0.08, 0.08, 0.09);
        $content .= self::text(350, 315, 9, 'Pago: '.self::money($invoice['paid']).'   Saldo: '.self::money($invoice['balance']), 'F1', 0.35, 0.35, 0.35);
        $content .= self::text(48, 315, 9, 'Estado: '.$invoice['status'], 'F2', 0.35, 0.35, 0.35);

        if ($invoice['notes']) {
            $content .= self::text(48, 275, 10, 'Notas', 'F2');
            $content .= self::text(48, 258, 9, $invoice['notes'], 'F1', 0.25, 0.25, 0.25);
        }

        $content .= self::line(48, 95, 548, 95, 0.86, 0.86, 0.88);
        $content .= self::text(48, 72, 8, $invoice['footer'], 'F1', 0.42, 0.42, 0.42);

        return self::render($content, $logoObject);
    }

    public static function checkInForm(array $data): string
    {
        $content = '';
        $content .= self::rect(0, 792, 595, 50, 0.06, 0.06, 0.07);
        $content .= self::rect(0, 735, 595, 57, 0.98, 0.72, 0.14);
        $content .= self::text(48, 805, 19, 'FICHA DE CHECK-IN', 'F2', 1, 1, 1);
        $content .= self::text(48, 764, 10, 'Documento para confirmacao dos dados do hospede no momento da chegada', 'F2', 0.08, 0.08, 0.09);
        $content .= self::text(420, 805, 10, 'Reserva: '.$data['reservation_code'], 'F2', 1, 1, 1);
        $content .= self::text(420, 785, 9, 'Emissao: '.$data['issued_at'], 'F1', 1, 1, 1);

        $content .= self::text(48, 705, 16, $data['property_name'], 'F2');
        $content .= self::text(48, 687, 9, $data['property_address'], 'F1', 0.25, 0.25, 0.25);
        $content .= self::text(48, 673, 9, $data['property_contacts'], 'F1', 0.25, 0.25, 0.25);

        $content .= self::rect(48, 610, 500, 34, 0.06, 0.06, 0.07);
        $content .= self::text(64, 623, 11, 'DADOS DO HOSPEDE', 'F2', 1, 1, 1);
        $content .= self::field('Nome completo', $data['guest_name'], 48, 575, 240);
        $content .= self::field('Contacto', $data['guest_phone'], 308, 575, 240);
        $content .= self::field('Email', $data['guest_email'], 48, 530, 240);
        $content .= self::field('NUIT', $data['guest_nuit'], 308, 530, 240);
        $content .= self::field('Tipo de documento', $data['document_type'], 48, 485, 240);
        $content .= self::field('Numero do documento', $data['document_number'], 308, 485, 240);
        $content .= self::field('Pais', $data['guest_country'], 48, 440, 240);

        $content .= self::rect(48, 385, 500, 34, 0.06, 0.06, 0.07);
        $content .= self::text(64, 398, 11, 'DADOS DA RESERVA', 'F2', 1, 1, 1);
        $content .= self::field('Quarto', $data['room'], 48, 350, 155);
        $content .= self::field('Entrada', $data['check_in'], 220, 350, 155);
        $content .= self::field('Saida', $data['check_out'], 393, 350, 155);
        $content .= self::field('Adultos', $data['adults'], 48, 305, 155);
        $content .= self::field('Criancas', $data['children'], 220, 305, 155);
        $content .= self::field('Pequeno-almoco', $data['breakfast_included'], 393, 305, 155);
        $content .= self::field('Origem', $data['source'], 48, 260, 155);
        $content .= self::field('Estado', $data['status'], 220, 260, 155);
        $content .= self::field('Total', $data['total'], 393, 260, 155);

        $content .= self::rect(48, 175, 500, 44, 0.96, 0.96, 0.97);
        $content .= self::text(64, 198, 9, 'Confirmo que os dados acima estao correctos e declaro ter recebido e aceite as politicas de reserva, pagamento, cancelamento, check-in, check-out, limpeza, danos e convivencia do alojamento.', 'F1', 0.2, 0.2, 0.2);

        $content .= self::line(48, 115, 250, 115, 0.1, 0.1, 0.1);
        $content .= self::line(345, 115, 548, 115, 0.1, 0.1, 0.1);
        $content .= self::text(48, 95, 9, 'Assinatura do hospede', 'F2', 0.25, 0.25, 0.25);
        $content .= self::text(345, 95, 9, 'Assinatura da recepcao', 'F2', 0.25, 0.25, 0.25);
        $content .= self::text(48, 72, 8, 'Documento gerado pelo AYA LodgeOS para arquivo operacional do alojamento.', 'F1', 0.42, 0.42, 0.42);

        return self::render($content);
    }

    private static function render(string $content, ?array $logoObject = null): string
    {
        $resources = '<< /Font << /F1 4 0 R /F2 5 0 R >>';

        if ($logoObject) {
            $resources .= ' /XObject << /Logo 7 0 R >>';
        }

        $resources .= ' >>';

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources '.$resources.' /Contents 6 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
            '<< /Length '.strlen($content)." >>\nstream\n".$content."endstream",
        ];

        if ($logoObject) {
            $objects[] = '<< /Type /XObject /Subtype /Image /Width '.$logoObject['width'].' /Height '.$logoObject['height'].' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($logoObject['data'])." >>\nstream\n".$logoObject['data']."\nendstream";
        }

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xrefOffset."\n%%EOF";
    }

    private static function text(float $x, float $y, int $size, mixed $text, string $font = 'F1', float $r = 0, float $g = 0, float $b = 0): string
    {
        return "BT\n{$r} {$g} {$b} rg\n/{$font} {$size} Tf\n{$x} {$y} Td\n(".self::escape($text).") Tj\nET\n";
    }

    private static function field(string $label, mixed $value, float $x, float $y, float $width): string
    {
        $content = self::text($x, $y + 24, 8, $label, 'F2', 0.45, 0.45, 0.45);
        $content .= self::strokeRect($x, $y, $width, 24, 0.82, 0.82, 0.84);
        $content .= self::text($x + 8, $y + 8, 9, filled($value) ? $value : '-', 'F1', 0.1, 0.1, 0.1);

        return $content;
    }

    private static function rect(float $x, float $y, float $w, float $h, float $r, float $g, float $b): string
    {
        return "{$r} {$g} {$b} rg\n{$x} {$y} {$w} {$h} re f\n";
    }

    private static function strokeRect(float $x, float $y, float $w, float $h, float $r, float $g, float $b): string
    {
        return "{$r} {$g} {$b} RG\n{$x} {$y} {$w} {$h} re S\n";
    }

    private static function line(float $x1, float $y1, float $x2, float $y2, float $r, float $g, float $b): string
    {
        return "{$r} {$g} {$b} RG\n{$x1} {$y1} m {$x2} {$y2} l S\n";
    }

    private static function jpegLogoObject(?string $path): ?array
    {
        if (! $path) {
            return null;
        }

        $fullPath = storage_path('app/public/'.$path);

        if (! is_file($fullPath)) {
            return null;
        }

        $image = @getimagesize($fullPath);

        if (! $image || $image[2] !== IMAGETYPE_JPEG) {
            return null;
        }

        return [
            'width' => $image[0],
            'height' => $image[1],
            'data' => file_get_contents($fullPath),
        ];
    }

    private static function money(mixed $value): string
    {
        return number_format((float) $value, 2).' MZN';
    }

    private static function escape(mixed $value): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $value) ?: (string) $value;
        $text = substr($text, 0, 120);

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
