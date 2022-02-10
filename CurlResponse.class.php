<?php

class CurlResponse
{

    /**
     * Array associativo contendo os dados da resposta.
     * Esta variável será populada apenas se o tipo de dados for XML/JSON.
     * 
     * @var array
     */
    public $responseData;

    /**
     * Array associativo contendo os dados do cabeçalho
     * 
     * @var array
     */
    public $headers;

    /**
     * Inteiro contendo o código de status HTTP (200 Ok, 404 Not Found, etc)
     * 
     * @var int
     */
    public $statusCode;

    /**
     * String contendo o texto literal retornado da requisição.
     * 
     * @var string
     */
    public $responseText;

    /**
     * Texto de erro do cURL
     * 
     * @var string
     */
    public $error;

    /**
     * Lista de códigos de estado HTTP
     */
    private $arrStatusCodeText = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'Ok',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'Im Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        444 => 'Connection Closed Without Response',
        451 => 'Unavailable For Legal Reasons',
        499 => 'Client Closed Request',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
        599 => 'Network Connect Timeout Error',
    ];


    
    /**
     * Cria uma novo objeto com os dados da resposta do servidor.
     * Todos os parâmetros são opcionais. É possível instanciar a classe e definir os parâmetros posteriormente.
     *
     * @param  array  $body Array contendo os dados do corpo da requisição. Será populado apenas se o tipo de dados for XML/JSON.
     * @param  int    $intStatusCode Código de status HTTP (200, 301, 400, 404, etc.)
     * @param  array  $arrHeaders Array contendo os dados do cabeçalho da resposta
     * @param  string $responseText Texto literal da resposta do servidor
     * @param  string    $error Texto de erro cURL
     *
     * @return void
     */
    public function __construct($body = null, $intStatusCode = 200, $arrHeaders = [], $responseText = '', $error = '')
    {
        $this->responseData = $body;
        $this->statusCode = $intStatusCode;
        $this->headers = $arrHeaders;
        $this->responseText = $responseText;
        $this->error = $error;
    }

    
    /**
     * Retorna o valor do array de dados como propriedade do objeto.
     * Ex.: $curlResponse->data
     *
     * @param  string $name Nome do atributo
     *
     * @return mixed
     */
    public function __get($name)
    {
        return (isset($this->responseData[$name]) ? $this->responseData[$name] : null);
    }

    public function __toString()
    {
        return $this->responseText ?: '';
    }
    
    
    /**
     * Retorna um array com os dados do corpo da resposta do servidor.
     * 
     * @return array
     */
    public function getResponseData()
    {
        return $this->responseData;
    }

    /**
     * Retorna os dados do cabeçalho da resposta do servidor.
     * 
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Retorna o código de estado HTTP ('status code')
     * 
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Retorna o texto correspondente ao código de estado HTTP ('status code')
     * 
     * @return string
     */
    public function getStatusText()
    {
        return $this->arrStatusCodeText[$this->statusCode];
    }

    /**
     * Retorna o texto completo do código e descriçao do estado HTTP ('status code')
     * 
     * @return string
     */
    public function getHTTPStatusText()
    {
        return $this->statusCode . ' - ' . $this->getStatusText();
    }

    /**
     * Retorna o corpo do texto de resposta do servidor
     * 
     * @return string
     */
    public function getResponseText()
    {
        return $this->responseText;
    }

    /**
     * Retorna a mensagem de erro de cURL, se houver. Caso não haja, retorna uma string vazia.
     * 
     * @return string
     */
    public function getCurlErrorMsg()
    {
        return $this->error;
    }

    
    /**
     * Verifica se o status da resposta do servidor é um status de erro.
     * Ex.: 400 (Bad Request), 401 (Unauthenticated), 404 (Not Found), etc.
     *
     * @return bool Retorna True se for um status de erro, ou False, caso contrário.
     */
    public function isStatusError()
    {
        return $this->statusCode >= 400;
    }

    /**
     * Verifica se a requisição possui erro de retuisição cURL ou se o status HTTP retornado é um status de erro do servidor.
     */
    public function isError()
    {
        return ($this->error != '') || $this->isStatusError();
    }

}
