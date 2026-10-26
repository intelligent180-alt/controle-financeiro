<?php

declare(strict_types=1);

/*
 * Copie este arquivo para config/config.local.php e preencha os dados
 * fornecidos pelo painel da hospedagem. O arquivo local e ignorado pelo Git.
 */
return [
    'database' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'controle_financeiro',
        'username' => 'usuario_mysql',
        'password' => 'troque-esta-senha',
        'charset' => 'utf8mb4',
    ],
];
