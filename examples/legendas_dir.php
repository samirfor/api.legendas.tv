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

function download($subtitle, $file)
{
    echo "Baixando {$subtitle->arquivo} de {$subtitle->uploader}...\n";
    $filename = $subtitle->download();
    echo "Arquivo {$filename} baixado!\n";
    if (preg_match('/\.(rar|zip)$/', $filename, $compExt)) {
        $outputFile = $file.$compExt[0];
        exec("mv $filename ".TORRENTDIR.'/'.$outputFile);
        return $outputFile;
    } else {
        die("ERRO Formato inesperado. Debug: $outputFile");
    }
}

function descompacta($fileRealPath)
{
    //echo "UNZIP php ".dirname(__FILE__)."/descompactador.php -f \"$fileRealPath\"";
    passthru("php ".dirname(__FILE__)."/descompactador.php -f \"$fileRealPath\"");
}

/* Começa a treta :D */
try {
    $legendastv = new LegendasTv();
    $legendastv->login('samirfor', '980244');

    $files = scandir(TORRENTDIR);
    foreach ($files as $file) {
        if (is_dir(TORRENTDIR.'/'.$file || preg_match("/\.br\..{3}$/", $file))) {
            continue;
        }
        if (preg_match('/\.(avi|mp4|mkv)/', $file, $s)) {
            $file = basename($file, $s[0]);
            // substitui pontos e traços por espaços
            $termoBusca = preg_replace('/[\.|-]/', ' ', $file);
            echo "Procurando legenda para $termoBusca ...\n";
            //tenta a pesquisa com o nome inteiro
            $subtitles = $legendastv->search($termoBusca, 'Português-BR');
            if (!is_array($subtitles)) {
                throw new \Exception('Erro na busca.', 1);
                die();
            }
            if (!empty($subtitles) && $subtitles[0]) {
                $zipRarFile = download($subtitles[0], $file);
                descompacta(getcwd().'/'.$zipRarFile);
            } else {
                //nao achou com o nome inteiro, tenta só com o episodio, e baixa o primeiro encontrado
                $termoBusca = preg_replace('/(EXTENDED|PDTV|WEB|HDTV|480p|720p|1080p).*/', '', $termoBusca);
                echo "Procurando legenda para $termoBusca ...\n";
                $subtitles = $legendastv->search($termoBusca, 'Português-BR');
                if (!empty($subtitles) && $subtitles[0]) {
                    $zipRarFile = download($subtitles[0], $file);
                    descompacta(getcwd().'/'.$zipRarFile);
                } else {
                    echo "Legenda de $file não encontrada :(\n";
                }
            }
        }
    }
} catch (Exception $e) {
    die($e->getMessage());
}
