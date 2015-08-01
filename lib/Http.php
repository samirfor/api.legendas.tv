<?php

namespace LegendasTv;

/**
 *
 */
class Http
{
    private $url;
    private $cookiejar;

    public function __construct($url = null)
    {
        $this->url = $url;
        $this->cookiejar = __DIR__.'/.cookies';
    }

    /**
     * Efetua uma requisição ao site do Legendas.TV.
     *
     * @param  string
     * @param  bool   Req. Ajax
     * @param  array     Query a ser enviada por post
     * @param  string    GET (default) ou POST
     *
     * @return array Array com o Conteúdo da página, info do curl e header
     *
     * @throws Exception Se o curl não for bem sucedido
     */
    public function request($url, $xmlHttpRequest = false, $params = array(), $method = 'GET')
    {
        if ($method == 'GET') {
            $query = array_filter(array_map(function ($k, $s) {
                return $s ? $k.':'.urlencode($s) : null;
            }, array_keys($params), $params));
            $url = $url.implode('/', $query);
        }

        if (!file_exists($this->cookiejar)) {
            $fh = fopen($this->cookiejar, 'w');
            fwrite($fh, '');
            fclose($fh);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.0.11) Gecko/2009060215 Firefox/3.0.11 (.NET CLR 3.5.30729)');
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiejar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiejar);
        if ($xmlHttpRequest) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Requested-With: XMLHttpRequest'));
        }
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }

        $content = curl_exec($ch);

        if ($content === false) {
            throw new Exception('Legendas.tv fora do ar');
        }

        preg_match('/^(.*?)\r\n\r\n(.*?)$/msU', $content, $match);
        $header = $match[1];
        $content = $match[2];
        $info = curl_getinfo($ch);

        curl_close($ch);

        return array($content, $info, $header);
    }

    public function xmlHttpRequest($url, $params = array(), $method = 'GET')
    {
        return $this->request($url, true, $params, $method);
    }

    public function httpRequest($url, $params = array(), $method = 'GET')
    {
        return $this->request($url, false, $params, $method);
    }

    /**
     * TODO
     */
    public function cookieStillAlive()
    {

    }
}
