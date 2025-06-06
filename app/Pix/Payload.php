<?php

namespace App\Pix;

class Payload
{

    /**
     * IDs do Payload do Pix
     * @var string
     */
    const ID_PAYLOAD_FORMAT_INDICATOR = '00';
    const ID_MERCHANT_ACCOUNT_INFORMATION = '26';
    const ID_MERCHANT_ACCOUNT_INFORMATION_GUI = '00';
    const ID_MERCHANT_ACCOUNT_INFORMATION_KEY = '01';
    const ID_MERCHANT_ACCOUNT_INFORMATION_DESCRIPTION = '02';
    const ID_MERCHANT_CATEGORY_CODE = '52';
    const ID_TRANSACTION_CURRENCY = '53';
    const ID_TRANSACTION_AMOUNT = '54';
    const ID_COUNTRY_CODE = '58';
    const ID_MERCHANT_NAME = '59';
    const ID_MERCHANT_CITY = '60';
    const ID_ADDITIONAL_DATA_FIELD_TEMPLATE = '62';
    const ID_ADDITIONAL_DATA_FIELD_TEMPLATE_TXID = '05';
    const ID_CRC16 = '63';

    /**
     * Chave do Pix
     * @var string
     */
    private $pixKey;

    /**
     * Descrição do Pagamento
     * @var string
     */
    private $description;

    /**
     * Nome do recebedor (titular da conta)
     * @var string
     */
    private $merchantName;

    /**
     * Cidade do recebedor (titular da conta)
     * @var string
     */
    private $merchantCity;

    /**
     * ID da transação pix
     * @var string
     */
    private $txid;

    /**
     * Valor da transação
     * @var string
     */
    private $amount;

    /**
     * Método responsável por definir o valor de $pixKey
     * @var string 
     */
    public function setPixKey(string $pixKey){
        $this->pixKey = $pixKey;
        return $this;
    }

    /**
     * Método responsável por definir o valor de $description
     * @var string 
     */
    public function setDescription(string $description){
        $this->description = $description;
        return $this;
    }

    /**
     * Método responsável por definir o valor de $merchantName
     * @var string 
     */
    public function setMerchantName(string $merchantName){
        $this->merchantName = $merchantName;
        return $this;
    }

    /**
     * Método responsável por definir o valor de $merchantCity
     * @var string 
     */
    public function setMerchantCity(string $merchantCity){
        $this->merchantCity = $merchantCity;
        return $this;
    }

    /**
     * Método responsável por definir o valor de $txid
     * @var string 
     */
    public function setTxid(string $txid){
        $this->txid = $txid;
        return $this;
    }
    /**
     * Método responsável por definir o valor de $amount
     * @var float 
     */
    public function setAmount(float $amount){
        $this->amount = (string)number_format($amount, 2, '.', '');
        if ($this->amount == 0) {
            $this->amount = '';
        }
        return $this;
    }

    /**
     * Método responsável por retornar o valor completo de um objeto do payload
     * @var string $id
     * @var string $value
     * @return string $id.$size.$value
     */
    public function getValue(string $id, string $value){
        $size = str_pad(strlen($value), 2, '0', STR_PAD_LEFT);
        return $id . $size . $value;
    }

    /**
     * Método responsável por retornar o valor completos da informação da conta
     * @return string $id.$size.$value
     */
    private function getMerchantAccountInformation(){
        //DOMÍNIO DO BANCO
        $gui = $this->getValue(self::ID_MERCHANT_ACCOUNT_INFORMATION_GUI, 'br.gov.bcb.pix');
        //CHAVE DO PIX
        $key = $this->getValue(self::ID_MERCHANT_ACCOUNT_INFORMATION_KEY, $this->pixKey);
        //DESCRIÇÃO DO PAGAMENTO
        $description = strlen($this->description) ? $this->getValue(self::ID_MERCHANT_ACCOUNT_INFORMATION_DESCRIPTION, $this->description) : '';
        //RETORNA O VALOR COMPLETO DO ID 26 (INFORMAÇÕES DA CONTA DO RECEBEDOR)
        //ID 26 (INFORMAÇÕES DA CONTA DO RECEBEDOR) + ID 00 (DOMÍNIO DO BANCO) + ID 01 (CHAVE DO PIX) + ID 02 (DESCRIÇÃO DO PAGAMENTO)
        return $this->getValue(self::ID_MERCHANT_ACCOUNT_INFORMATION, $gui . $key . $description);
    }

    /**
     * Método responsável por retornar os valores completos do campo adicional do pix (TXID)
     * @return string 
     */
    private function getAdditionalDataFieldTemplate(){
        //TXID (ID DA TRANSAÇÃO PIX)
        $txid = $this->getValue(self::ID_ADDITIONAL_DATA_FIELD_TEMPLATE_TXID, $this->txid);

        return $this->getValue(self::ID_ADDITIONAL_DATA_FIELD_TEMPLATE, $txid);
    }

    /**
     * Método responsável por calcular o valor da hash de validação do código pix
     * @return string
     */
    private function getCRC16($payload){
        //ADICIONA DADOS GERAIS NO PAYLOAD
        $payload .= self::ID_CRC16 . '04';

        //DADOS DEFINIDOS PELO BACEN
        $polinomio = 0x1021;
        $resultado = 0xFFFF;

        //CHECKSUM
        if (($length = strlen($payload)) > 0) {
            for ($offset = 0; $offset < $length; $offset++) {
                $resultado ^= (ord($payload[$offset]) << 8);
                for ($bitwise = 0; $bitwise < 8; $bitwise++) {
                    if (($resultado <<= 1) & 0x10000) $resultado ^= $polinomio;
                    $resultado &= 0xFFFF;
                }
            }
        }

        //RETORNA CÓDIGO CRC16 DE 4 CARACTERES
        return self::ID_CRC16 . '04' . strtoupper(dechex($resultado));
    }

    /**
     * Método responsável por gerar o código completo do payload do pix
     * @return string
     */
    public function getPayload(){
        $payload = $this->getValue(self::ID_PAYLOAD_FORMAT_INDICATOR, '01') .
                   $this->getMerchantAccountInformation() .
                   $this->getValue(self::ID_MERCHANT_CATEGORY_CODE, '0000') .
                   $this->getValue(self::ID_TRANSACTION_CURRENCY, '986') .
                   $this->getValue(self::ID_TRANSACTION_AMOUNT, $this->amount) .
                   $this->getValue(self::ID_COUNTRY_CODE, 'BR') .
                   $this->getValue(self::ID_MERCHANT_NAME, $this->merchantName) .
                   $this->getValue(self::ID_MERCHANT_CITY, $this->merchantCity) .
                   $this->getAdditionalDataFieldTemplate();

        //ADICIONA O CRC16 NO FINAL DO PAYLOAD
        $payload .= $this->getCRC16($payload);
        //RETORNA O PAYLOAD COMPLETO
        return $payload;
    }
    /* *
     * Método responsável por calcular o CRC16 do payload
     * @param string $payload
     * @return int
     * /
    private function crc16($payload)
    {
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($payload); $i++) {
            $crc ^= ord($payload[$i]) << 8;
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = ($crc << 1) ^ 0x1021;
                } else {
                    $crc <<= 1;
                }
            }
        }
        return $crc & 0xFFFF;
    }*/
}