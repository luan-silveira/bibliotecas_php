<?php

/**
 * Classe 
 */
class FTP {

    const TEMPFOLDER = '/ftp_tmp';

    private $resFtp;

    /**
     * Cria uma nova conexão FTP.
     * 
     * @param string $strServidor Nome do Servidor 
     * @param string $strUsuario  Usuário 
     * @param string $strSenha    Senha 
     * @param int    $intPorta    (Opcional) Porta (Padrão: 21) 
     * @param bool   $boolPassivo (Opcional) Habilita o modo passivo. \
     *                            "No modo passivo, as conexões de dados são iniciadas pelo cliente, ao invés do servidor. 
     *                             Pode ser necessário se o cliente estiver atrás de um firewall" \
     *                             (https://www.php.net/manual/pt_BR/function.ftp-pasv.php) 
     * @param int    $intTimeout  Tempo limite, em segundos (Padrão: 90 segundos) 
     */
    public function __construct($strServidor, $strUsuario, $strSenha, $intPorta = 21, $boolPassivo = false, $intTimeout = 90)
    {
        if (!$this->resFtp = ftp_connect($strServidor, $intPorta, $intTimeout)) {
            throw new Exception("Erro ao conectar-se ao servidor FTP '$strServidor'");
        }

        if (! @ftp_login($this->resFtp, $strUsuario, $strSenha)) {
            throw new Exception("Erro ao autenticar o usuário e senha do FTP.");
        }

        if ($boolPassivo) {
            ftp_pasv($this->resFtp, true);
        }
    }

    public function __destruct()
    {
        ftp_close($this->resFtp);
    }

    /**
     * Redireciona chamada de funções para as funções ftp_*.
     * Esta função substitui o 'camelCase' por 'undeline'. 
     * 
     * Ex: 
     *  * $ftp->setOption(...) =>  ftp_set_option($ftp, ...)
     *  * $ftp->chdir(...)     =>  ftp_chdir($ftp, ...)
     * 
     * @param string $name      Nome da função (sem o prefixo 'ftp_')
     * @param array  $arguments Lista de Argumentos
     * 
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $name = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name));
        return call_user_func("ftp_$name", $this->resFtp, ...$arguments);
    }

    public function chdir($strDir)
    {
        return @ftp_chdir($this->resFtp, $strDir);
    }

    /**
     * Copia um arquivo de um diretório a outro no FTP.
     * 
     * @param string $strOrigem  Arquivo de origem
     * @param string $strDestino Arquivo de destino
     * 
     * @return bool Retorna True em caso de sucesso ou False em caso de falha.
     */
    public function copy($strOrigem, $strDestino)
    {
        $strTmpDir = sys_get_temp_dir() . self::TEMPFOLDER;
        if (! is_dir($strTmpDir)) {
            mkdir($strTmpDir, 0777, true);
        }
        $strOrigem  = $this->getCaminhoAbsoluto($strOrigem);
        $strDestino = $this->getCaminhoAbsoluto($strDestino);

        $strTmpArquivo = $strTmpDir . '/' . basename($strOrigem);

        if (! $this->get($strTmpArquivo, $strOrigem)) return false;
        if (! $this->put($strDestino, $strTmpArquivo)) return false;
        unlink($strTmpArquivo);

        return true;
    }

    /**
     * Envia um arquivo para o servidor FTP.
     * 
     * Utiliza a função `ftp_put()` ( https://www.php.net/manual/pt_BR/function.ftp-put)
     * 
     * @param string $strArquivoRemoto O caminho para o arquivo remoto.
     * @param string $strArquivoLocal  O caminho para o arquivo local.
     * @param int    $intModo          O modo de transferência. Deve ser `FTP_ASCII` ou `FTP_BINARY` (Padrão).
     * 
     * @return bool Retorna `true` em caso de sucesso ou `false` em caso de falha.
     */
    public function put($strArquivoRemoto, $strArquivoLocal, $intModo = FTP_BINARY)
    {
        return @ftp_put($this->resFtp, $strArquivoRemoto, $strArquivoLocal, $intModo);
    }
    
    /**
     * Copia um arquivo do servidor FTP.
     * 
     * Utiliza a função `ftp_get()` (https://www.php.net/manual/pt_BR/function.ftp-get)
     * 
     * @param string $strArquivoLocal  O caminho para o arquivo local (será sobrescrito se já existir).
     * @param string $strArquivoRemoto O caminho para o arquivo remoto.
     * @param int    $intModo          O modo de transferência. Deve ser `FTP_ASCII` ou `FTP_BINARY` (Padrão).
     * 
     * @return bool Retorna `true` em caso de sucesso ou `false` em caso de falha.
     */
    public function get($strArquivoLocal, $strArquivoRemoto, $intModo = FTP_BINARY)
    {
        return @ftp_get($this->resFtp, $strArquivoLocal, $strArquivoRemoto, $intModo);
    }

    /**
     * Verifica se o arquivo informado é um diretório (pasta)
     * 
     * @param string $strCaminho Caminho do arquivo ou pasta
     * 
     * @return bool
     */
    public function isDir($strCaminho)
    {
        $strDirAnterior = $this->pwd();
        $boolChDir      = $this->chdir($strCaminho);

        //-- Se mudou o diretório, então o caminho informado é uma pasta.
        //-- Sendo assim, muda novamente para o diretório anterior
        if ($boolChDir) {
            $this->chdir($strDirAnterior);
        }

        return $boolChDir;
    }

    /**
     * Cria uma nova pasta no servidor FTP.
     * 
     * @param string $strDir        Caminho da pasta a ser criada.
     * @param int    $intModo       (Opcional) Número octal para definir as permissões da pasta (Padrão: 0777). 
     * @param bool   $boolRecursivo (Opcional) Define se criará as pastas recursivamente.
     * 
     * @return bool Retorna `true` caso a pasta seja criada ou `false` caso contrário. 
     */
    public function mkdir($strDir, $intModo = 0777, $boolRecursivo = true)
    {
        $boolCriou = false;

        $strDir = str_replace('\\', '/', $strDir);
        if ($boolRecursivo && (strpos($strDir, '/') !== false)) {

            $strPastaPai = ($strDir[0] == '/' ? '/' : '');
            $arrPastas = explode('/', trim($strDir, '/'));

            foreach ($arrPastas as $strPasta) {
                $strPastaFTP = $strPastaPai . $strPasta;

                if (!$this->isDir($strPastaFTP)) {
                    if (! $boolCriou = @ftp_mkdir($this->resFtp, $strPastaFTP)) {
                        return $boolCriou;
                    }
                    ftp_chmod($this->resFtp, $intModo, $strPastaFTP);
                }

                $strPastaPai .= "$strPasta/";
            }
        } else {
            if ($boolCriou = @ftp_mkdir($this->resFtp, $strDir)) {
                ftp_chmod($this->resFtp, $intModo, $strDir);
            }
        }


        return $boolCriou;
    }

    /**
     * Verifica se um arquivo existe. 
     * 
     * Obs.: Serve apenas para arquivos. Se o caminho informado for um diretório, irá retornar `false`.
     * 
     * @param string $strArquivo Caminho do arquivo
     * 
     * @return bool
     */
    public function fileExists($strArquivo)
    {
        return $this->size($strArquivo) >= 0;
    }

    /**
     * Retorna o caminho absoluto, com base no diretório atual do FTP.
     * Se o caminho não começar com barra ('/'), adiciona o caminho atual do FTP.
     * 
     * @param string $strCaminho Caminho do arquivo FTP
     * 
     * @return string
     */
    private function getCaminhoAbsoluto($strCaminho)
    {
        $strCaminho = str_replace("\\", '/', trim($strCaminho));
        if ($strCaminho[0] != '/') {
            return $this->pwd() . '/' . $strCaminho;
        }

        return $strCaminho;
    }

}