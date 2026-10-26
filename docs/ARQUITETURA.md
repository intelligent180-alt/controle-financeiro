# Arquitetura do Controle Financeiro

## Tecnologias usadas

- PHP 8.1+ no backend;
- MySQL/MariaDB na hospedagem e SQLite no desenvolvimento local;
- PDO para acesso parametrizado ao banco;
- HTML5, CSS3 e JavaScript puro no painel web;
- sessões PHP para o painel e Bearer API keys para integrações externas;
- JSON e REST para comunicação entre sistemas;
- Android Java, WebView e Gradle no APK;
- HTTPS/DNS como ponto único de acesso web e mobile;
- OpenAPI 3.0 para contrato e documentação da API.

## Divisão de responsabilidades

```text
Interface web / APK / ERP externo
              │
              ▼
       API PHP versionada
       autenticação + regras
              │
              ▼
       PDO / MySQL ou SQLite
```

O frontend não decide regras críticas. Ele envia intenção, como “compra de
R$ 1.200 em quatro parcelas”; o backend valida, identifica cartão, calcula
competência, divide centavos e grava.

## Como a lógica funciona

### 1. Login do painel

`api/auth.php` recebe CPF, usuário ou e-mail e senha. A senha é comparada com
`password_verify`, uma sessão `SF_AUTH` é criada e todas as consultas usam o
`user_id` da sessão.

### 2. Integrações externas

O administrador cria uma chave em `api/developer-keys.php`. O token completo
não é salvo; somente seu hash SHA-256 é armazenado. `api/v1.php` procura o hash,
verifica validade, escopo, dono e limite de requisições.

### 3. Lançamento de cartão

`api/finance.php` contém as funções centrais de competência e parcelas. A API
REST carrega esse arquivo em modo biblioteca, reaproveitando:

- identificação de compra no crédito;
- quinto dia útil com feriados nacionais;
- deslocamento de competência;
- divisão de parcelas em centavos;
- vínculo por cartão e final;
- status pendente/pago;
- filtros por usuário.

### 4. Banco

Todas as tabelas têm `user_id` e as consultas usam parâmetros PDO. O MySQL não
fica publicamente acessível; somente o PHP hospedado conhece hostname, banco,
usuário e senha.

## Como criar sozinho a lógica

1. Defina o modelo de dados: usuários, cartões, transações, receitas e metas.
2. Defina as regras em funções puras antes de criar telas: mês de referência,
   quinto dia útil, parcelas e status.
3. Crie endpoints pequenos e previsíveis: listar, criar, excluir e dashboard.
4. Valide no backend tudo que veio do cliente; nunca confie no valor calculado
   no JavaScript.
5. Separe autenticação de autorização: login prova quem é; escopo prova o que
   a integração pode fazer.
6. Filtre todas as queries pelo usuário autenticado ou dono da chave.
7. Documente cada campo com OpenAPI e forneça exemplos cURL.
8. Teste primeiro SQLite local, depois MySQL em homologação e por fim produção.
9. Registre `requestId` em erros para conseguir investigar sem expor senha.
10. Faça backup antes de migrations; use `CREATE TABLE IF NOT EXISTS` para
    atualizações não destrutivas.

## Checklist de produção

- HTTPS válido no domínio;
- senha do banco somente em `config.local.php`/variáveis de ambiente;
- `display_errors=Off`;
- `pdo_mysql` habilitado;
- CORS restrito aos domínios conhecidos;
- chave de leitura para testes e chave de escrita somente para integrações
  confiáveis;
- rotação e revogação de chaves;
- backup MySQL antes de atualizar arquivos;
- monitoramento de `/api/v1.php/health`;
- APK apontando para o domínio estável, nunca para IP ou `localhost`.
