<?php

$basePath = dirname(__DIR__);
$configCache = $basePath.'/bootstrap/cache/config.php';

if (is_file($configCache) && ! unlink($configCache)) {
    throw new RuntimeException('Não foi possível limpar o cache de configuração antes dos testes.');
}

if (getenv('DB_CONNECTION') !== 'mysql' || getenv('DB_DATABASE') !== 'gideao_testing') {
    throw new RuntimeException('Testes devem usar exclusivamente o banco MySQL gideao_testing.');
}

require $basePath.'/vendor/autoload.php';
