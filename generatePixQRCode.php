<?php

/*require __DIR__ . '/vendor/autoload.php';

use App\Pix\Payload;
use Mpdf\QrCode\QrCode;
use Mpdf\QrCode\Output;*/

/**
 * Função para gerar o QR Code e o código Pix
 *
 * @param string $pixKey Chave Pix
 * @param string $description Descrição
 * @param string $merchantName Nome do Comerciante
 * @param string $merchantCity Cidade do Comerciante
 * @param string $txid ID da Transação
 * @param float $amount Valor
 * @return array Retorna um array com o código Pix e o QR Code em Base64
 */
function generatePixQRCode($pixKey, $description, $merchantName, $merchantCity, $txid, $amount) {
	
	sc_include_library('prj','vendor', 'vendor/autoload.php');

    // INSTANCIA PRINCIPAL DO PAYLOAD PIX
    $obPayload = new \App\Pix\Payload();
    // Configure o payload Pix
	$obPayload->setPixKey($pixKey)
              ->setDescription($description)
              ->setMerchantName($merchantName)
              ->setMerchantCity($merchantCity)
              ->setTxid($txid)
              ->setAmount($amount);

    // CÓDIGO DE PAGAMENTO PIX
    $obPayloadQrCode = $obPayload->getPayload();

    // GERANDO O QRCODE
    $obQrCode = new \Mpdf\QrCode\QrCode($obPayloadQrCode);
    $output = new \Mpdf\QrCode\Output\Png();
    $image = $output->output($obQrCode, 400);

    // CRIANDO A IMAGEM DO QR CODE
    $qrImage = imagecreatefromstring($image);

    // CARREGANDO O LOGO
    $logoPath = __DIR__ . '/logo.png'; // Caminho para o logo
    if (file_exists($logoPath)) {
        $logo = imagecreatefrompng($logoPath);

        // REDIMENSIONANDO O LOGO
        $qrWidth = imagesx($qrImage);
        $qrHeight = imagesy($qrImage);
        $logoWidth = $qrWidth * 0.2; // 20% do tamanho do QR Code
        $logoHeight = $qrHeight * 0.2;
        $logoResized = imagecreatetruecolor($logoWidth, $logoHeight);
        imagealphablending($logoResized, false);
        imagesavealpha($logoResized, true);
        imagecopyresampled(
            $logoResized,
            $logo,
            0,
            0,
            0,
            0,
            $logoWidth,
            $logoHeight,
            imagesx($logo),
            imagesy($logo)
        );

        // SOBREPOR O LOGO NO CENTRO DO QR CODE
        $logoX = ($qrWidth - $logoWidth) / 2;
        $logoY = ($qrHeight - $logoHeight) / 2;
        imagecopy(
            $qrImage,
            $logoResized,
            $logoX,
            $logoY,
            0,
            0,
            $logoWidth,
            $logoHeight
        );

        // LIMPAR MEMÓRIA DO LOGO
        imagedestroy($logo);
        imagedestroy($logoResized);
    }

    // GERANDO A IMAGEM FINAL
    ob_start();
    imagepng($qrImage);
    $finalImage = ob_get_clean();

    // LIMPAR MEMÓRIA
    imagedestroy($qrImage);

    // RETORNANDO O QR CODE E O CÓDIGO PIX
    return [
        'pixCode' => $obPayloadQrCode, // Código Pix
        'qrCode' => 'data:image/png;base64,' . base64_encode($finalImage) // QR Code com logo em Base64
    ];
?>