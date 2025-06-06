<?php

require __DIR__ . '/vendor/autoload.php';

use App\Pix\Payload;
use Mpdf\QrCode\QrCode;
use Mpdf\QrCode\Output;

// Corrige o valor recebido para float
$amount = $_POST['amount'] ?? '0';
// Remove tudo que não for número ou vírgula/ponto
$amount = preg_replace('/[^\d,\.]/', '', $amount);
// Troca vírgula por ponto (caso o usuário digite 1.234,56 ou 1234,56)
$amount = str_replace(',', '.', $amount);
$amount = floatval($amount);

// INSTANCIA PRINCIPAL DO PAYLOAD PIX
$obPayload = (new Payload)->setPixKey($_POST['pixKey'])
                          ->setDescription($_POST['description'])
                          ->setMerchantName($_POST['merchantName'])
                          ->setMerchantCity($_POST['merchantCity'])
                          ->setTxid($_POST['txid'])
                          ->setAmount($amount);

// CÓDIGO DE PAGAMENTO PIX
$obPayloadQrCode = $obPayload->getPayload();

// GERANDO O QRCODE
$obQrCode = (new QrCode($obPayloadQrCode));
$image = (new Output\Png)->output($obQrCode, 400);

// CRIANDO A IMAGEM DO QR CODE
$qrImage = imagecreatefromstring($image);

// CARREGANDO O LOGO
$logoPath = __DIR__ . '/logo.png'; // Caminho para o logo
if (!file_exists($logoPath)) {
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Logo não encontrado no caminho especificado.'
    ]);
    exit;
}
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

// GERANDO A IMAGEM FINAL
ob_start();
imagepng($qrImage);
$finalImage = ob_get_clean();

// LIMPAR MEMÓRIA
imagedestroy($qrImage);
imagedestroy($logo);
imagedestroy($logoResized);

// RETORNANDO O QR CODE E O CÓDIGO PIX EM FORMATO JSON
header('Content-Type: application/json');
echo json_encode([
    'pixCode' => $obPayloadQrCode, // Código Pix
    'qrCode' => 'data:image/png;base64,' . base64_encode($finalImage) // QR Code com logo em Base64
]);