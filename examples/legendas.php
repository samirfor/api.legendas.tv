#!/usr/bin/php
<?php

namespace LegendasTv;

require dirname(__FILE__) . '/../lib/LegendasTv.php';

/**
 * Função para emular o famoso readln :P.
 *
 * @return string O que o cara digitou no console
 */
function readln()
{
    while (false !== ($line = fgets(STDIN))) {
        return $line;
    }
}


/* tratamento de argumentos
 * l, lang    language
 * f, first   baixa o primeiro link
 */
$options = getopt('l:fd', array('first', 'lang::', 'logged::'));
$search = implode(' ', array_slice($argv, sizeof($options) + 1));

/* Começa a treta :D */
try {
    $legendastv = new LegendasTv();
    if (!isset($options['logged'])) {
        $legendastv->login('samirfor', '980244');
    }
    $subtitles = $legendastv->search($search, @$options['l'] ?: (@$options['lang'] ?: 'Qualquer idioma'));
    // var_dump($subtitles);
    // exit;
} catch (Exception $e) {
    echo $e->getMessage() . "\n";
    exit(1);
}

if (array_key_exists('d', $options)) { # caso flag "d" esteja ativada retorna apenas os destaques
    $subtitles = array_filter($subtitles, function ($subtitle) {
        return $subtitle->destaque;
    });
} else { # caso contrário, ordena por destaque e downloads
    usort($subtitles, function ($a, $b) {
        if ($a->destaque and !$b->destaque) {
            return -1;
        }
        if ($b->destaque and !$a->destaque) {
            return 1;
        }

        return $a->downloads > $b->downloads ? -1 : 1;
    });
}

if (count($subtitles) > 1 and !(array_key_exists('f', $options) or array_key_exists('first', $options))) {
    echo "Qual das legendas abaixo desejas baixar?\n\n";
    foreach ($subtitles as $id => $subtitle) {
        echo sprintf(
            '[%'.(count($subtitles) > 10 ? 2 : 1)."d] %s %s (%d/dl %s)\n",
            $id,
            $subtitle->destaque ? '*' : ' ',
            $subtitle->arquivo,
            $subtitle->downloads,
            $subtitle->idioma
        );
    }
    $option = (int) readln();

    while (!isset($subtitles[$option])) {
        echo 'Opção inválida. Digite novamente: ';
        $option = readln();
    }

    $subtitle = $subtitles[$option];
} elseif ($subtitles) {
    $subtitle = $subtitles[0]; // A única :)
} else {
    echo "Nenhuma legenda encontrada :(\n";
    exit(1);
}

echo "Baixando {$subtitle->arquivo}...\n";
$filename = $subtitle->download();
echo "Arquivo {$filename} baixado!\n";
