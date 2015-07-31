<?php

namespace LegendasTv;

class Legenda
{
    /**
     * Dados gerais da legenda.
     */
    private $data = array();

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Efetua a requisição de um arquivo ao servidor.
     *
     * @param  string
     *
     * @return string Arquivo ou link para o arquivo
     */
    public function download($filename = null)
    {
        // list($file, $info, $header) = LegendasTV::request("http://legendas.tv/pages/downloadarquivo/{$this->id}");
        list($file, $info, $header) = LegendasTV::request("http://legendas.tv/downloadarquivo/{$this->id}");
        if ($filename === null) {
            /* Antigo funcionamento. Agora não retorna mais o filename no header
            // preg_match('/filename="(.*?)"/', $header, $filename);
            */
            preg_match('/Location: http:\/\/f\.legendas\.tv\/\w\/(.*)/', $header, $filename);

            // O formato abaixo é o nome de retorno do arquivo do legendas.tv, que não diz muita coisa
            $filename = trim($filename[1]);

            // Alteramos para o nome de arquivo refletir o nome completo da legenda
            $filename = $this->data['arquivo'].substr($filename, strrpos($filename, '.'));
        }
        $filename = str_replace(array('/', '\\'), '_', $filename);
        file_put_contents($filename, $file);

        return $filename;
    }

    /**
     * Método mágico para retornar informações da Legenda.
     *
     * @param  string
     *
     * @return mixed
     *
     * @throws InvalidArgumentException se o parâmetro solicitado não existir
     *
     * @todo   Buscar informações extras por demanda através do link
     *         info.php sem o parâmetro c
     */
    public function __get($prop)
    {
        if ($prop == 'download_link' and !isset($this->data['download_link'])) {
            $this->data['download_link'] = $this->download(null, true);
        }

        if (!isset($this->data[$prop])) {
            throw new InvalidArgumentException();
        }

        return $this->data[$prop];
    }
}
