# api.legendas.tv
API para acesso aos dados e legendas do Legendas.tv

# Uso
```shell
# - Lista os destaques mais recentes, permitindo que você 
#   selecione a legenda que será baixada.
# Saída: arquivo compactado com a(s) legenda(s) dentro.
php examples/legendas.php -d
```

```shell
# - Procura arquivos de vídeo na pasta passada como parâmetro, 
#   baixa, descompacta, escolhe a mais adequada e renomeia.
# Saída: legenda(s) srt com mesmo nome do(s) arquivo(s) de vídeo contido(s) na pasta.
php examples/legendas_dir.php ./
```
É necessário ter os programas rar e zip (comandos **unrar** e **unzip**).
