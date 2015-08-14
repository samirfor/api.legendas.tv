<?php

namespace LegendasTv;

// TODO: criar uma opção para forçar a escolha da legenda mais atual
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
        rename($filename, TORRENTDIR.'/'.$outputFile);
        // exec("mv $filename ".TORRENTDIR.'/'.$outputFile);
        return $outputFile;
    } else {
        die("ERRO Formato inesperado. Debug: $outputFile");
    }
}

function descompacta($fileRealPath)
{
    //echo "UNZIP php ".dirname(__FILE__)."/descompactador.php -f \"$fileRealPath\"";
    passthru('php '.dirname(__FILE__)."/descompactador.php -f \"$fileRealPath\"");
}

function pesquisarLegenda($termoBusca, $legendastv)
{
    $subtitles = $legendastv->search($termoBusca, 'Português-BR');
    // destaque tem preferência
    usort($subtitles, function ($a, $b) {
        if ($a->destaque and !$b->destaque) {
            return -1;
        }
        if ($b->destaque and !$a->destaque) {
            return 1;
        }

        return $a->downloads > $b->downloads ? -1 : 1;
    });

    return $subtitles;
}

/* Começa a treta :D */
try {
    $legendastv = new LegendasTv();
    $legendastv->login('samirfor', '980244');

    $files = scandir(TORRENTDIR);
    foreach ($files as $file) {
        if (is_dir(TORRENTDIR.'/'.$file) || preg_match("/\.br\..{3}$/", $file)) {
            // echo "Ignorado $file\n";
            continue;
        }
        if (preg_match('/\.(avi|mp4|mkv)/', $file, $s)) {
            $file = basename($file, $s[0]);
            // substitui pontos e traços por espaços
            $termoBusca = preg_replace('/[\.|-|_]/', ' ', $file);
            preg_match('/^.+S\d+E\d+/i', $termoBusca, $resultadoBusca);
            if (isset($resultadoBusca[0])) {
                $termoBusca = $resultadoBusca[0];
            } else {
                $termoBusca = preg_replace('/(EXTENDED|PDTV|WEB|HDTV|480p|720p|1080p).*/', '', $termoBusca);
            }
            echo "Procurando legenda para $termoBusca ...\n";
            $subtitles = pesquisarLegenda($termoBusca, $legendastv);
            if (!empty($subtitles) && $subtitles[0]) {
                $zipRarFile = download($subtitles[0], $file);
                descompacta(getcwd().'/'.$zipRarFile);
            } else {
                echo "Legenda não encontrada :(\n\n";
            }
        }
    }
} catch (Exception $e) {
    die($e->getMessage());
}
