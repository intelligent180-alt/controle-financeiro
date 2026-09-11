<?php

declare(strict_types=1);

/*
 * As credenciais ficam fora do código e devem ser definidas no ambiente do
 * Apache/PHP. Este arquivo somente centraliza os nomes e endpoints usados
 * pela camada de provedores.
 */
return [
    'serpro' => [
        'clientId' => (string)(getenv('SF_SERPRO_CLIENT_ID') ?: ''),
        'clientSecret' => (string)(getenv('SF_SERPRO_CLIENT_SECRET') ?: ''),
        'userCpf' => (string)(getenv('SF_SERPRO_USER_CPF') ?: ''),
        'tokenUrl' => (string)(getenv('SF_SERPRO_TOKEN_URL') ?: 'https://apigateway.conectagov.estaleiro.serpro.gov.br/oauth2/jwt-token'),
        'cnpjUrl' => (string)(getenv('SF_SERPRO_CNPJ_URL') ?: 'https://apigateway.conectagov.estaleiro.serpro.gov.br/api-cnpj-basica/v2/basica/{document}'),
    ],
    'serasa' => [
        'endpoint' => (string)(getenv('SF_SERASA_ENDPOINT') ?: ''),
        'tokenUrl' => (string)(getenv('SF_SERASA_TOKEN_URL') ?: ''),
        'token' => (string)(getenv('SF_SERASA_TOKEN') ?: ''),
        'clientId' => (string)(getenv('SF_SERASA_CLIENT_ID') ?: ''),
        'clientSecret' => (string)(getenv('SF_SERASA_CLIENT_SECRET') ?: ''),
        'apiKey' => (string)(getenv('SF_SERASA_API_KEY') ?: ''),
        'authMode' => strtolower((string)(getenv('SF_SERASA_AUTH_MODE') ?: 'bearer')),
        'documentField' => (string)(getenv('SF_SERASA_DOCUMENT_FIELD') ?: 'document'),
        'reportCode' => (string)(getenv('SF_SERASA_REPORT_CODE') ?: ''),
    ],
];
