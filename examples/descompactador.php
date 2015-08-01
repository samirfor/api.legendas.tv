<?php

namespace LegendasTv;

function delTree($dir)
{
    $iterador = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new \RecursiveIteratorIterator(
        $iterador,
        \RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $file) {
        if ($file->isDir()) {
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }
    rmdir($dir);
}

$options = getopt('f:');

if (!isset($options['f'])) {
    die('Especifique um arquivo compactado.');
}

try {
    $fileAbsPath = realpath($options['f']);
    $currentDir = dirname($fileAbsPath);
    $fileNoExt = pathinfo($fileAbsPath)['filename'];
    $tmpDir = '/tmp/.'.uniqid();
    // echo $fileAbsPath."\n";
    // echo $currentDir."\n";
    // echo $fileNoExt."\n";
    // echo $tmpDir."\n";
    // exit;
    mkdir($tmpDir);

    preg_match('/(rar|zip)$/', $options['f'], $extensao);
    if (!is_array($extensao)) {
        throw new \Exception('Extensao inesperada.', 1);

        return;
    }
    if ($extensao[0] === 'rar') {
        exec("cd {$tmpDir} && unrar e \"{$fileAbsPath}\"");
    } elseif ($extensao[0] === 'zip') {
        exec("cd {$tmpDir} && unzip \"{$fileAbsPath}\"");
    }

    $srtExato = $tmpDir.'/'.$fileNoExt.'.srt';
    if (file_exists($srtExato)) {
        // achou um srt com nome exato do release
        rename($srtExato, $currentDir.'/'.basename($srtExato));
    } else {
        echo "Tentando uma parecida...\n";
        $achou = false;
        $arraySrt = glob($tmpDir.'/*.srt');
        $outputSrt = $arraySrt[0]; // se nada der certo, pega a primeira
        preg_match('/([P|H|S]DTV|BluRay|WEB.{0,1}DL)/i', $fileNoExt, $rip);
        preg_match("/(\d{3,}p)/i", $fileNoExt, $resolution);
        preg_match("/(.*)[\.| |\-]([^.]*)$/", $fileNoExt, $group);

        if (isset($rip[0]) && preg_match('/WEB.{0,1}DL/i', $rip[0])) {
            $rip[0] = 'WEB-DL';
        }

        if (isset($resolution[0]) && isset($rip[0]) && isset($group[2])) {
            // verifica resolution (p.e. 1080p) e rip (p.e. HDTV)
            echo "I1: resolução ({$resolution[0]}), rip ({$rip[0]}) e grupo ({$group[2]})\n";
            foreach ($arraySrt as $srt) {
                if (preg_match("/{$resolution[0]}/i", $srt) &&
                    preg_match("/{$rip[0]}/i", $srt) &&
                    preg_match("/{$group[2]}/i", $srt)) {
                    echo "Achou! I1\n";
                    $achou = true;
                    $outputSrt = $srt;
                    break;
                }
            }
        }
        if (!$achou && isset($resolution[0]) && isset($rip[0])) {
            // verifica resolution (p.e. 1080p) e rip (p.e. HDTV)
            echo "I2: resolução ({$resolution[0]}) e rip ({$rip[0]})\n";
            foreach ($arraySrt as $srt) {
                if (preg_match("/{$resolution[0]}/i", $srt) && preg_match("/{$rip[0]}/i", $srt)) {
                    echo "Achou! I2\n";
                    $achou = true;
                    $outputSrt = $srt;
                    break;
                }
            }
        }
        if (!$achou && isset($group[2]) && isset($rip[0])) {
            // verifica grupo (p.e. DIMENSION) e rip (p.e. HDTV)
            echo "I3: grupo ({$group[2]}) e rip ({$rip[0]})\n";
            foreach ($arraySrt as $srt) {
                if (preg_match("/{$group[2]}/i", $srt) && preg_match("/{$rip[0]}/i", $srt)) {
                    echo "Achou! I3\n";
                    $achou = true;
                    $outputSrt = $srt;
                    break;
                }
            }
        }
        if (!$achou && isset($rip[0]) && isset($resolution[0])) {
            // verifica somente rip (p.e. HDTV) e resolução (p.e. 1080p)
            echo "I4: rip ({$rip[0]}) e resolução ({$resolution[0]})\n";
            foreach ($arraySrt as $srt) {
                if (preg_match("/{$rip[0]}/i", $srt) && preg_match("/{$resolution[0]}/i", $srt)) {
                    echo "Achou! I4\n";
                    $achou = true;
                    $outputSrt = $srt;
                    break;
                }
            }
        }
        if (!$achou && isset($rip[0])) {
            // verifica somente rip (p.e. HDTV)
            echo "I5: rip ({$rip[0]})\n";
            foreach ($arraySrt as $srt) {
                if (preg_match("/{$rip[0]}/i", $srt)) {
                    echo "Achou! I5\n";
                    $achou = true;
                    $outputSrt = $srt;
                    break;
                }
            }
        }
        if (!$achou && isset($group[2])) {
            // verifica somente grupo (p.e. DIMENSION)
            echo "I6: grupo ({$group[2]})\n";
            foreach ($arraySrt as $srt) {
                if (preg_match("/{$group[2]}/i", $srt)) {
                    echo "Achou! I6\n";
                    $achou = true;
                    $outputSrt = $srt;
                    break;
                }
            }
        }
        if (!$achou) {
            echo "I7: primeiro srt.\n";
        }

        echo basename($outputSrt)." encontrada e renomeada.\n";
        rename($outputSrt, $currentDir.'/'.$fileNoExt.'.srt');
    }
    echo "Legenda $fileNoExt.srt extraída.\n\n";
    unlink($options['f']);
    delTree($tmpDir);
} catch (\Exception $e) {
    die($e->getMessage());
}
