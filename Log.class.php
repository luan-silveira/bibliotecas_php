<?php

/**
 * Classe criada para escreve registros de _log_ de forma prática. 
 * 
 * Utlizada para fins de depuração (debug)
 * 
 * @author Luan Christian Nascimento da Silveira
 */
class Log
{

    private $strCaminhoArquivo;
    private static $instancia;


    public function __construct($strNomeArquivo = null)
    {
        if (!trim($strNomeArquivo)) $strNomeArquivo = '4ulog_' . date('Y-m-d');

        $strDir = getcwd() . '/tmp/log';
        if (! is_dir($strDir)) {
            if (! mkdir($strDir, 0777, true)) {
                throw new Exception("Erro ao criar o diretório '$strDir'");
            }
        }

        $strCaminhoArquivo = "$strDir/$strNomeArquivo.log";
        $this->strCaminhoArquivo = $strCaminhoArquivo;
    }

    public static function write($strTexto, $boolTime = true)
    {
       return self::get()->writeLog($strTexto, $boolTime);
    }

    /**
     * Função estática para criar um registro de log.
     *
     * @param string $strTexto Texto
     * @param boolean $boolTime Informa se irá imprimir a data e a hora (True/False)
     * 
     * @return int|bool Retorna o mesmo que a função _file_put_contents()_
     */
    public function writeLog($strTexto, $boolTime = true)
    {
        if (! is_scalar($strTexto))  {
            $strTexto = print_r($strTexto, true);
        }
        
        if ($boolTime) {
            $arrTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $strArquivo = str_replace($_SERVER['DOCUMENT_ROOT'], '', $arrTrace[1]['file']);
            $intLine    = $arrTrace[1]['line'];

            $decTime = microtime(true);
            $intTime = (int) explode('.', $decTime)[1];

            $strTexto = date('d/m/Y H:i:s') . ".$intTime - $strArquivo:$intLine -->>  " . $strTexto;
        }
        
        $retorno =  file_put_contents($this->strCaminhoArquivo, $strTexto . "\n", FILE_APPEND);
        if ($retorno !== false) {
            chmod($this->strCaminhoArquivo, 0777);
        }
        return $retorno;
    }


    public static function get()
    {
        if (!self::$instancia) self::$instancia = new self();
        return self::$instancia;
    }
}
