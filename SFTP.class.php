<?php

/**
 * Classe para conexão e manipulação de servidor SFTP.
 * 
 * @method bool chmod (string $filename, int $mode) Modifica as permissões de um arquivo remoto.
 * @method bool mkdir(string $dirname , int $mode = 0777 , bool $recursive = false) Cria um diretório remoto.
 * @method bool rmdir(string $dirname) Remove um diretório remoto.
 * @method bool unlink(string $filename) Remove um arquivo remoto.
 * @method string realpath(string $filename) Retorna o caminho absoluto a partir de um caminho relativo de um arquivo ou diretório.
 * 
 * @author Luan Christian Nascimento da Silveira <luan@pelainternet.com.br>
 */
class SFTP
{

    private $ssh;
    private $sftp;

    private $strDirAtual = '/';

    /**
     * Inicia uma nova conexão SFTP.
     * 
     * @param string $strServidor Endereço do servidor SSH/SFTP
     * @param string $strUsuario  Nome de usuário
     * @param string $strSenha    Senha
     * @param int    $intPorta    (Opcional) Porta de conexao. Porta padrão: 22 
     */
    public function __construct($strServidor, $strUsuario, $strSenha, $intPorta = 22)
    {

        if (! $this->ssh = ssh2_connect($strServidor, $intPorta)) {
            throw new Exception("Erro ao conectar-se ao servidor SSH '$strServidor'");
        }

        if (! ssh2_auth_password($this->ssh, $strUsuario, $strSenha)) {
            throw new Exception("Erro ao autenticar o usuário e senha do SSH.");
        }

        if (! $this->sftp = ssh2_sftp($this->ssh)) {
            throw new Exception("Erro ao inicializar o SFTP.");
        }

    }

    public function __destruct()
    {
        $this->ssh = null;
        unset($this->ssh);
    }


    /**
     * Retorna o caminho remoto do SFTP.
     * 
     * Utilizado nas funções para abrir arquivos (fopen, opendir, etc.)
     * 
     * @return string Retorna o caminho da conexão SFTP no formato 'ssh2.sftp://[id]'
     */
    public function getEnderecoSFTP()
    {
        return 'ssh2.sftp://' . intval($this->sftp);
    }


    /**
     * Retorna o díretório atual de trabalho.
     * Equivalente à função getcwd()
     * 
     * @return string
     */
    public function getDirAtual()
    {
        return $this->strDirAtual;
    }

    /**
     * Retorna o endereço SFTP do diretório atual.
     * 
     * @return string
     */
    public function getEnderecoDirAtual()
    {
        return $this->getEnderecoSFTP() . ($this->strDirAtual != '/' ? $this->strDirAtual : '');
    }

    /**
     * Retorna o endereço SFTP do arquivo informado.
     * 
     * @param string $strArquivo Caminho do arquivo remoto.
     * 
     * @return string
     */
    public function getEndereco($strArquivo)
    {
        if ($strArquivo[0] == '/') {
            return $this->getEnderecoSFTP() . $strArquivo;
        }

        return $this->getEnderecoDirAtual() . '/' . $strArquivo;
    }


    /**
     * Retorna o conteúdo do arquivo do servidor SFTP
     * 
     * @param string $strArquivo Caminho para o arquivo remoto.
     * 
     * @return string|false Retorna os dados lidos ou `false` em caso de falha.
     */
    public function get($strArquivo)
    {
        return file_get_contents($this->getEndereco($strArquivo));
    }

    /**
     * Escreve dados em um arquivo no servidor SFTP
     * 
     * @param string $strArquivo Caminho para o arquivo remoto.
     * @param mixed  $dados      Dados a serem escritos.
     * @param string $intFlags   Flags para escrita de arquivo, de acordo com a função 
     *                           [`file_put_contents()`](https://www.php.net/manual/pt_BR/function.file-put-contents)
     * 
     * @return int|false Retorna o número de bytes escritos ou `false` em caso de falha.
     */
    public function put($strArquivo, $dados, $intFlags = 0)
    {
        return file_put_contents($this->getEndereco($strArquivo), $dados, $intFlags);
    }


    /**
     * Muda o diretório atual.
     * 
     * @param string $strDir Caminho do diretório
     * 
     * @return bool Retorna `true` em caso de sucesso ou `false` em caso de falha.
     */
    public function chdir($strDir)
    {
        if ($strDir === '') return false;

        if ($strDir == '/') {
            $this->strDirAtual = $strDir;
            return true;
        }

        if ($strDir[0] != '/' && $this->strDirAtual != '/') {
            $strDir = "{$this->strDirAtual}/$strDir";
        }

        $arrPastasReais = [];
        $arrPastas = explode('/', trim($strDir, '/'));

        foreach ($arrPastas as $strPasta) {
            if ($strPasta == '.') continue;

            if ($strPasta == '..') {
                if (count($arrPastasReais) == 0) return false;
                array_pop($arrPastasReais);
            } else {
                $arrPastasReais[] = $strPasta;
            }
        }

        $strDir = '/' .  implode('/', $arrPastasReais);
        if (($strDir != '/') && (! $this->fileExists($strDir))) return false;

        $this->strDirAtual = $strDir;

        return true;
    }

    /**
     * Executa a função 'opendir()' em uma pasta do SFTP.
     * 
     * @param string $strDir Caminho da pasta, com barra '/' no início. Exemplo: '/var/www/html/sis'
     * 
     * @return resource|false Retorna um resource gerado pela função 'opendir(), ou False em caso de falha'.
     */
    public function openDir($strDir)
    {
        return opendir($this->getEndereco($strDir));
    }

    /**
     * Executa a função 'scandir()' em uma pasta do SFTP.
     * 
     * @param string $strDir Caminho da pasta, com barra '/' no início. Exemplo: '/var/www/html/sis'
     * 
     * @return array|false Retorna um array com todos os arquivos encontrados, ou False em caso de falha'.
     */
    public function scanDir($strDir)
    {
        return scandir($this->getEndereco($strDir));
    }

    /**
     * Retorna um objeto [`FilesystemIterator`](https://www.php.net/manual/pt_BR/class.filesystemiterator.php) 
     * para percorrer o diretório informado.
     *
     * @param string $strDir (Opcional) Diretório a ser percorrido. Se não for informado, retorna o diretório atual.
     * @return FilesystemIterator
     */
    public function getDirIterator($strDir = null)
    {
        return new FilesystemIterator($strDir ?  $this->getEndereco($strDir) : $this->getEnderecoDirAtual());
    }

    /**
     * Verifica se o caminho informado é um diretório (pasta)
     * 
     * @param string $strDir Caminho do diretório
     * 
     * @return bool
     */
    public function isDir($strDir)
    {
        return is_dir($this->getEndereco($strDir));
    }

     /**
     * Verifica se o caminho informado é um arquivo
     * 
     * @param string $strDir Caminho do arquivo
     * 
     * @return bool
     */
    public function isFile($strArquivo)
    {
        return is_file($this->getEndereco($strArquivo));
    }

    /**
     * Retorna uma lista de arquivos de um determinado diretório.
     *
     * @param string  $strDir        (Opcional) Caminho do diretório. Se não for informado, usa o diretório atual.
     * @param boolean $boolDetalhado (Opcional) Opção para retornar um objeto [`SplFileInfo`](https://www.php.net/manual/pt_BR/class.splfileinfo.php).
     *                                          Por padrão, retorna somente o nome do arquivo (`false`).
     * @return void
     */
    public function getListaArquivos($strDir = null, $boolDetalhado = false)
    {
        $i = $this->getDirIterator($strDir);
        if (! $i->valid()) return false;

        $arrArquivos = [];
        foreach ($i as $objArquivo) {
            $arrArquivos[] = $boolDetalhado ? $objArquivo : $objArquivo->getFilename();
        }

        return $arrArquivos;
    }

    /**
     * Baixa os arquivos do servidor para a máquina local, mantendo os arquivos no servidor.
     *
     * @param string  $strDirLocal  Diretório local a serem baixados os arquivos
     * @param string  $strDirRemoto (Opcional) Diretório remoto dos arquivos a serem baixados.
     *                              Se não for informado, usa o diretório atual.
     * @param integer $intChmod     Define as permissões dos arquivos e pastas criados. Padrão: 0777
     * 
     * @return bool Retorna `true` em caso de sucesso, ou `false` em caso de falha.
     */
    public function getArquivos($strDirLocal, $strDirRemoto = null, $intChmod = 0777)
    {
        if (! $strDirRemoto) $strDirRemoto = $this->getDirAtual();

        if (! is_dir($strDirLocal) && ! mkdir($strDirLocal, $intChmod, true)) {
            return false;
        }

        if (! $dir = $this->openDir($strDirRemoto)) return false;

        while ($strArquivo = readdir($dir)) {
            if (in_array($strArquivo, ['.', '..'])) continue;

            $strArquivoLocal  = "$strDirLocal/$strArquivo";
            $strArquivoRemoto = "$strDirRemoto/$strArquivo";

            if ($this->isDir($strArquivoRemoto)) {
                if (!$this->getArquivos($strArquivoLocal, $strArquivoRemoto, $intChmod)) return false;
            } else {
                if (! copy($this->getEndereco($strArquivoRemoto), $strArquivoLocal)) return false;
                @chmod($strArquivoLocal, $intChmod);
            }

        }
        closedir($dir);

        return true;
    }

    public function putArquivos($strDirLocal, $strDirRemoto = null, $intChmod = 0777)
    {
        if (! is_dir($strDirLocal)) return false;
        if (! $strDirRemoto) $strDirRemoto = $this->getDirAtual();

        if (! $this->isDir($strDirRemoto) && ! $this->mkdir($strDirRemoto, $intChmod, true)) {
            return false;
        }

        $i = new FilesystemIterator($strDirLocal);
        foreach ($i as $objArquivo) {
            $strArquivoLocal  = $objArquivo->getPathname();
            $strArquivoRemoto = "$strDirRemoto/{$objArquivo->getFilename()}";

            if ($objArquivo->isDir()) {
                if (! $this->putArquivos($strArquivoLocal, $strArquivoRemoto, $intChmod)) return false;
            } else {
                if (! copy($strArquivoLocal, $this->getEndereco($strArquivoRemoto))) return false;
                $this->chmod($strArquivoRemoto, 0777);
            }
        }

        return true;
    }

    /**
     * Verifica se o arquivo informado existe.
     * 
     * Funciona também com pastas.
     * 
     * @param string $strDir Caminho do arquivo
     * 
     * @return bool
     */
    public function fileExists($strArquivo)
    {
        return file_exists($this->getEndereco($strArquivo));
    }

    public function __call($name, $arguments)
    {
        $name = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name));
        return call_user_func("ssh2_sftp_$name", $this->sftp, ...$arguments);
    }
}
