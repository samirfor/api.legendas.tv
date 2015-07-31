<?php

namespace LegendasTv;

require dirname(__FILE__).'/../lib/Http.php';
require dirname(__FILE__).'/../lib/Legenda.php';

/**
 * Classe para pesquisa e download de legendas do Legendas.tv.
 */
class LegendasTv
{
    /**
     * Página para a busca das legendas
     * Link é montado através da combinação $resource/%1/%2/%3 onde:
     * %1 = Termo da busca
     * %2 = Código da linguagem
     * %3 = Tipo de resultados esperados (todos, packs ou destaques apenas).
     */
    private $resource = 'http://legendas.tv/util/carrega_legendas_busca/%s/%s/%s';

    /**
     * Tradução para as diferentes linguagens que podem ser pesquisadas.
     */
    protected $languages = array(
        'Qualquer idioma' => '-', # default
        'Português-BR' => 1, 'ptbr' => 1,
        'Inglês' => 2, 'en' => 2,
        'Espanhol' => 3, 'es' => 3,
        'Português-PT' => 10, 'pt' => 10,
        'Alemão' => 5, 'gr' => 5,
        'Árabe' => 11, 'ar' => 11,
        'Búlgaro' => 15, 'bl' => 15,
        'Checo' => 12, 'ck' => 12,
        'Chinês' => 13, 'ch' => 13,
        'Coreano' => 14, 'kr' => 14,
        'Dinamarquês' => 7, 'dn' => 7,
        'Francês' => 4, 'fr' => 4,
        'Italiano' => 16, 'it' => 16,
        'Japonês' => 6, 'jp' => 6,
        'Norueguês' => 8, 'nr' => 8,
        'Polonês' => 17, 'pl' => 17,
        'Sueco' => 9, 'sw' => 9,
    );

    /**
     * Tipos de legendas para pesquisa.
     */
    protected $types = array(
        'Todos' => '-',
        'Pack' => 'p',
        'Destaque' => 'd',
    );

    /**
     * Efetua uma busca por legendas no site do legendas.tv.
     *
     * @param  string  O conteúdo da busca
     * @param  string  A linǵuagem da legenda
     *
     * @return array
     *
     * @throws Exception se o idioma for inválido
     *
     * @todo   Rolar a paginação nos resultados da busca
     *         Retornar uma coleção de legendas, não um array, com métodos
     *         para ordenar por campos como por exemplo, destaque ou downloads
     */
    public function search($search, $lang = 'Qualquer idioma', $type = 'Todos')
    {
        if (!isset($this->languages[$lang])) {
            throw new Exception('Idioma inválido');
        }

        $link = sprintf(
            $this->resource,
            urlencode($search),
            $this->languages[$lang],
            $this->types[$type]
        );

        $http = new Http();
        list($page) = $http->xmlHttpRequest($link);
        $subtitles = $this->parse($page);

        return $subtitles;
    }

    /**
     * Efetua o parse de uma página de listagem de legendas.
     *
     * @param  string
     *
     * @return array Todas as legendas identificadas
     *
     * @todo   Centralizar o parse de outras páginas aqui também.
     */
    private function parse($page)
    {
        $regex = '/';
        $regex .= 'div class="(.*?)">.*?<a href="(.*?)">(.*?)<.*?p class="data">';
        $regex .= '(\d+?) downloads, nota (\d+?), enviado por .*?>(.*?)<\/a> ';
        $regex .= 'em (.*?) <\/p>.*?<.*?alt="(.*?)".*?<\/div>';
        $regex .= '/';
        preg_match_all($regex, $page, $match);

        $parsed = array();
        foreach ($match[0] as $key => $m) {
            $id = explode('/', $match[2][$key]);
            $parsed[] = new Legenda(array(
                'destaque' => $match[1][$key] == 'destaque',
                'id' => $id[2],
                'link' => $match[2][$key],
                'arquivo' => $match[3][$key],
                'downloads' => $match[4][$key],
                'nota' => $match[5][$key],
                'uploader' => $match[6][$key],
                'data' => $match[7][$key],
                'idioma' => $match[8][$key],
            ));
        }

        return $parsed;
    }

    /**
     * Loga um usuario junto ao legendas.tv.
     *
     * @param string $username Nome de usuário no legendas.tv
     * @param string $password Senha
     *
     * @return booelan
     *
     * @throws Exception Em caso de problemas no login
     */
    public function login($username, $password)
    {
        $http = new Http();
        list($content) = $http->httpRequest(
            'http://legendas.tv/login',
            array(
                'data[User][username]' => $username,
                'data[User][password]' => $password,
            ),
            'POST'
        );

        # Trata possíveis erros
        if (strpos($content, 'Usuário ou senha inválidos') !== false) {
            # Remover cookies faz com que não caia no captcha, a princípio
            $this->deletaCookie();
            throw new Exception('Não foi possível se logar no site: Usuário ou senha inválidos.');
        } elseif (strpos($content, 'Palavras de segurança incorretas') !== false) {
            $this->deletaCookie();
            throw new Exception('Muitas tentativas de login incorretas, captcha encontrado');
        }

        # Usuário (provavelmente) logado :P
        return true;
    }

    public function deletaCookie($path = null)
    {
        if ($path === null) {
            $path = __DIR__.'/.cookies';
        }
        unlink($path);
    }
}
