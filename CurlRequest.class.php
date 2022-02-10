<?php

/**
 * Classe criada para criar requisições HTTP utilizando a biblioteca cURL.
 * 
 * @author Luan Christian Nascimento da Silveira
 * Data da Criação: 17/12/2019
 */
class CurlRequest
{

    const JSON = 0;
    const XML = 1;
    const URL_ENCODED = 2;

    private $curl;
    private $strUrl;
    private $intType;
    private $strMethod;
    private $arrMethods = ["GET", "POST"];
    private $strXmlRoot = '<xmlrequest />';
    private $arrRequestHeaders = [];

    private $logData = false;    
    
    /**
     * Cria um novo objeto CurlRequest.
     * 
     * @param string $strUrl URL
     * @param string $strMethod (Opcional) Método da requisição (GET, POST, etc.). O método padrão é GET.
     * @param string $intType (Opcional) Tipo de dados da requisição ('JSON'/'XML').
     * @param string $strXmlRoot (Opcional) Elemento raiz do XML.
     */
    public function __construct($strUrl, $strMethod = 'GET', $intType = self::JSON, $strXmlRoot = null)
    {
        $this->curl = curl_init();
        $this->strUrl = $strUrl;
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_HEADER, true);

        $this->setMethod($strMethod);
        $this->setType($intType);

        if ($strXmlRoot)
            $this->strXmlRoot = $strXmlRoot;
    }

    /**
     * Cria um novo objeto CurlRequest com o método GET.
     * 
     * @param string $strUrl URL
     * @param mixed  $data Dados da requisição. Pode ser informado uma string de acordo com o formato (XML/JSON/Url Encoded) 
     *                     ou um array associativo contendo os dados no formato ['chave' => 'valor'] 
     * @param string $intType Tipo de dados da requisição ('JSON'/'XML')
     * 
     * @return CurlRequest Retorna o objeto da requisição
     */
    public static function get($strUrl, $data = null, $intType = self::JSON)
    {
        $request = new self($strUrl, 'GET', $intType);
        if ($data) $request->setData($data);
        return $request;
    }

    /**
     * Cria um novo objeto CurlRequest com o método POST.
     * 
     * @param string $strUrl URL
     * @param mixed  $data Dados da requisição. Pode ser informado uma string de acordo com o formato (XML/JSON/Url Encoded) 
     *                     ou um array associativo contendo os dados no formato ['chave' => 'valor'] 
     * @param string $intType Tipo de dados da requisição ('JSON'/'XML')
     * 
     * @return CurlRequest Retorna o objeto da requisição
     */
    public static function post($strUrl, $data = null, $intType = self::JSON)
    {
        $request = new self($strUrl, 'POST', $intType);
        if ($data) $request->setData($data);
        return $request;
    }

    /**
     * Define o método da requisição.
     * 
     * @param string $strMethod Método da requisição (GET, POST, PUT, DELETE, OPTIONS)
     * 
     * @return CurlRequest
     */
    public function setMethod($strMethod)
    {
        $strMethod = strtoupper($strMethod);
        $this->strMethod = $strMethod;
        if (in_array($strMethod, $this->arrMethods)) {
            if ($strMethod == "POST") {
                curl_setopt($this->curl, CURLOPT_POST, TRUE);
            } elseif ($strMethod != "GET") {
                curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $strMethod);
            }
        }

        return $this;
    }

    /**
     * Define o tipo de dados da requisição. 
     * 
     * @param string $intType Tipo de dados da requisição ('JSON'/'XML')
     * 
     * @return CurlRequest
     */
    public function setType($intType)
    {
        $this->intType = $intType;
            if ($intType == self::JSON) {
                $strMime = 'json';
            } elseif ($intType == self::XML) {
                $strMime = 'xml';
            } else {
                $strMime = 'x-www-form-urlencoded';
            }
            $this->addHeader("Content-Type: application/$strMime");
            if ($intType != self::URL_ENCODED) {
                $this->addHeader("Accept: application/$strMime");
            }
        
        return $this;
    }

    /**
     * Adiciona um array de cabeçalhos HTTP à requisição.
     * 
     * @param array $headers Array associativo contendo os dados do cabeçalho (['chave' => 'valor'])
     * 
     * @return CurlRequest
     */
    public function setHeaders($headers)
    {
        $this->arrRequestHeaders = $headers;
        return $this;
    }

    /**
     * Adiciona um item ao cabeçalho da requisição.
     * 
     * @param string $strHeader String no formato 'Item: valor';
     * 
     * @return CurlRequest
     */
    public function addHeader($strHeader)
    {
        list($key, $value) = explode(':', $strHeader, 2);
        $this->arrRequestHeaders[$key] = trim($value);
        return $this;
    }

    /**
     * Retorna os cabeçalhos da requisição 
     * 
     * @return array
     */
    public function getArrayHeaders()
    {
        $arrHeaders = [];
        foreach ($this->arrRequestHeaders as $key => $value) {
            $arrHeaders[] = "$key: $value";
        }

        return $arrHeaders;
    }

    /**
     * Busca o valor de um item do cabeçalho (header) da requisição.
     * 
     * @param string $strHeaderName Nome do item do cabeçalho
     * 
     * @return string Retorna o valor do item.
     */
    public function getHeader($strHeaderName)
    {
        return (!isset($this->arrRequestHeaders[$strHeaderName]) ? false : $this->arrRequestHeaders[$strHeaderName]);
    }


    /**
     * Define o array de dados que compõe o corpo da requisição.
     * 
     * @param mixed $data Dados da requisição. Pode ser informado uma string de acordo com o formato (XML/JSON/Url Encoded) 
     *                    ou um array associativo contendo os dados no formato ['chave' => 'valor'] 
     * 
     * @return CurlRequest
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function authBasic($strUsuario, $strSenha)
    {
        $strToken = base64_encode("$strUsuario: $strSenha");
        $this->addHeader("Authorization: Basic $strToken");
    }

    public function authBearerToken($strToken)
    {
        $this->addHeader("Authorization: Bearer $strToken");
    }
 

    /**
     * Envia a requisição ao servidor.
     * 
     * @return CurlResponse Retorna um novo objeto CurlResponse com os dados da resposta do servidor.
     */
    public function send()
    {
        if ($this->strMethod == 'POST') {
            if (!empty($this->data)) {
                if (is_array($this->data)){
                    if ($this->intType == self::JSON) {
                        $strData = json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    } elseif ($this->intType == self::XML) {
                        $xml = new SimpleXMLElement($this->strXmlRoot);
                        $this->xmlEncode($this->data, $xml);
                        $strData = $xml->asXML();
                    } else {
                        $strData = http_build_query($this->data);
                    }
                } else {
                    $strData = $this->data;
                }

                curl_setopt($this->curl, CURLOPT_POSTFIELDS, $strData);
                $this->logData($strData);
            }
        } elseif ($this->strMethod == 'GET') {
            if (!empty($this->data)) {
                $strData = http_build_query($this->data);
                $this->strUrl .= ((strpos($this->strUrl, '?') === false) ? '?' : '&') . $strData;
            }
        }

        curl_setopt($this->curl, CURLOPT_URL, $this->strUrl);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->getArrayHeaders());

        $strResponse = curl_exec($this->curl);
        
        $intHeaderSize = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
        $intHttpStatus = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $strError = curl_error($this->curl);
        curl_close($this->curl);

        $strHeader = substr($strResponse, 0, $intHeaderSize);
        $strBody = substr($strResponse, $intHeaderSize);       


        $response = [];
        $strHeaderAccept = $this->getHeader('Accept');

        if ($strHeaderAccept == 'application/json') {
            $response = json_decode($strBody, true);
        } elseif ($strHeaderAccept == 'application/xml') {
            $response = $this->xmlToArray($strBody);
        }

        $arrHeaders = $this->parseResponseHeader($strHeader);
        
        
        return new CurlResponse($response, $intHttpStatus, $arrHeaders, $strBody, $strError);
    }



    private function xmlEncode($data, &$xml)
    {
        foreach ($data as $strKey => $value) {
            if (is_array($value) || is_object($value)) {
                if (is_numeric($strKey))
                    $strKey = "item$strKey";
                $xmlChild = $xml->addChild($strKey);
                $this->xmlEncode((array) $value, $xmlChild);
            } else {
                $xml->addChild($strKey, $value);
            }
        }
    }

    private function xmlToArray($strXml)
    {
        $sxi = new SimpleXmlIterator($strXml);
        return $this->sxiToArray($sxi);
    }

    private function sxiToArray($sxi)
    {
        $arrDados = [];
        for ($sxi->rewind(); $sxi->valid(); $sxi->next()) {
            if (!array_key_exists($sxi->key(), $arrDados)) {
                $arrDados[$sxi->key()] = array();
            }
            if ($sxi->hasChildren()) {
                $arrDados[$sxi->key()] = $this->sxiToArray($sxi->current());
            } else {
                $arrDados[$sxi->key()] = strval($sxi->current());
            }
        }
        return $arrDados;
    }
    
    private function parseResponseHeader($strHeaders)
    {
        $strDelimitador = (strpos($strHeaders, "\r\n") === false) ? "\n" : "\r\n";
        $arrHeaders = explode($strDelimitador, $strHeaders);
        $arrRetHeaders = [];
        foreach ($arrHeaders as $header){
            if (strpos($header, ':') === false) continue;
            list($key, $value) = explode(':', $header, 2);
            $arrRetHeaders[$key] = trim($value);
        }
        
        return $arrRetHeaders;
    }

    private function logData($strData)
    {
        if (!$this->logData) return;
        
        $filename = realpath(__DIR__. '/../../log') .'/logCurlRequest.log';
        $content = "Dados da requisição:\n $strData\n";
        $content .= date('d/m/Y H:i:s');
        $content .= "\n----------------------\n\n";

        $res = file_put_contents($filename, $content, FILE_APPEND);
    }

}
