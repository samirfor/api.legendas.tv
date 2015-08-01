<?php

namespace LegendasTv;

if ($argv[1]) {
    if (is_dir($argv[1])) {
        define('TORRENTDIR', $argv[1]);
    } else {
        define('TORRENTDIR', '.');
    }
}

require dirname(__FILE__).'/../lib/LegendasTv.php';

$legendastv = new LegendasTv();
$files = scandir(TORRENTDIR);

foreach ($files as $file) {
    if (is_dir(TORRENTDIR.'/'.$file)) {
        continue;
    }
    if (preg_match('/\.(avi|mp4|mkv)/', $file, $s)) {
        $file = basename($file, $s[0]);
        // substitui pontos por espaços
        $termoBusca = preg_replace('/[\.|-]/', ' ', $file);
        echo "Procurando legenda para $termoBusca ...\n";
        //tenta a pesquisa com o nome inteiro
        $subtitles = $legendastv->search($termoBusca, 'Português-BR');
        if (!is_array($subtitles)) {
            throw new \Exception('Erro na busca.', 1);
            die();
        }
        if (!empty($subtitles) && $subtitles[0]) {
            $subtitle = $subtitles[0];
            echo "Baixando {$subtitle->arquivo}...\n";
            $filename = $subtitle->download();
            echo "Arquivo {$filename} baixado!\n";
            if (preg_match('/\.(rar|zip)/', $filename, $compExt)) {
                exec("mv $filename ".TORRENTDIR.'/'.$file.$compExt[0]);
            } else {
                exec("echo ERRO !!! debug: $file $compExt[0]");
            }
        } else {
            //nao achou com o nome inteiro, tenta só com o episodio, e baixa o primeiro encontrado
            $termoBusca = preg_replace('/(PDTV|WEB|HDTV|480p|720p|1080p).*/', '', $termoBusca);
            echo "Procurando legenda para $termoBusca ...\n";
            $subtitles = $legendastv->search($termoBusca, 'Português-BR');
            if (!empty($subtitles) && $subtitles[0]) {
                $subtitle = $subtitles[0];
                echo "Baixando {$subtitle->arquivo}...\n";
                $filename = $subtitle->download();
                echo "Arquivo {$filename} baixado!\n";
                if (preg_match('/\.(rar|zip)/', $filename, $compExt)) {
                    exec("mv $filename ".TORRENTDIR.'/'.$file.$compExt[0]);
                } else {
                    exec("echo ERRO !!! debug: $file $compExt[0]");
                }
            } else {
                echo "Legenda de $file não encontrada :(\n";
            }
        }
    }
}
