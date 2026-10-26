# API REST pública — Controle Financeiro

Versão atual: `1.0.0`<br>
Base de produção: `https://financeiro.byethost32.com/api/v1.php`

## Arquitetura de segurança

O banco de dados não é acessado pelo APK, navegador ou por integrações externas.
O fluxo é:

```text
Aplicação externa / APK / web
            ↓ HTTPS + Bearer API key
      api/v1.php
            ↓ PDO + regras do sistema
        MySQL ou SQLite
```

Cada chave pertence a um usuário e só pode consultar ou alterar os dados desse
usuário. A chave é armazenada no banco apenas como SHA-256; o token completo é
exibido uma única vez quando criado.

## Criar uma chave para um desenvolvedor

Somente o administrador autenticado pode criar chaves. A chamada usa a sessão
normal do painel:

```bash
curl -X POST "https://financeiro.byethost32.com/api/developer-keys.php" \
  -H "Content-Type: application/json" \
  -H "Cookie: SF_AUTH=SESSAO_DO_ADMIN" \
  -d '{"name":"Meu ERP","scopes":"read,write","rateLimit":120}'
```

Guarde o campo `key.token` em um cofre de segredos. Não coloque a chave no
frontend, Git, documentação pública ou APK distribuído para terceiros.

Escopos disponíveis:

- `read`: leitura de dados;
- `write`: criação e exclusão de movimentações e cartões;
- `all`: todos os escopos.

Para listar chaves, use `GET /api/developer-keys.php` com a sessão de admin.
Para revogar, use `DELETE /api/developer-keys.php?id=ID_DA_CHAVE`.

## Autenticação

Envie em todas as rotas privadas:

```http
Authorization: Bearer sf_live_sua_chave
```

Também é aceito `X-API-Key`, útil em alguns conectores. Nunca envie a senha do
MySQL para um cliente externo.

## Rotas

As rotas podem ser chamadas no formato REST com caminho ou pelo parâmetro
`resource`, que funciona mesmo quando o servidor não habilita `PATH_INFO`:

```text
/api/v1.php/transactions
/api/v1.php?resource=transactions
```

| Método | Recurso | Escopo | Uso |
|---|---|---|---|
| GET | `/health` | público | Verifica a API |
| GET | `/me` | `read` | Dados do dono da chave |
| GET | `/dashboard?month=AAAA-MM` | `read` | Dashboard completo da competência |
| GET | `/transactions?month=AAAA-MM` | `read` | Movimentações paginadas |
| GET | `/transactions/{id}` | `read` | Uma movimentação |
| POST | `/transactions` | `write` | Cria gasto/entrada e parcelas |
| DELETE | `/transactions/{id}` | `write` | Exclui uma movimentação do próprio usuário |
| GET | `/cards` | `read` | Cartões ativos sem número completo |
| POST | `/cards` | `write` | Cadastra cartão usando apenas final e BIN opcional |
| GET | `/incomes?month=AAAA-MM` | `read` | Recebimentos e holerites |

## Exemplos

### Saúde da API

```bash
curl "https://financeiro.byethost32.com/api/v1.php/health"
```

### Ler as contas de novembro de 2026

```bash
curl "https://financeiro.byethost32.com/api/v1.php/transactions?month=2026-11" \
  -H "Authorization: Bearer sf_live_sua_chave"
```

### Criar gasto no cartão com parcelas

```bash
curl -X POST "https://financeiro.byethost32.com/api/v1.php/transactions" \
  -H "Authorization: Bearer sf_live_sua_chave" \
  -H "Content-Type: application/json" \
  -d '{
    "description": "Compra de equipamento",
    "category": "Eletrônicos",
    "amount": 1200.00,
    "movementDate": "2026-10-10",
    "paymentMethod": "credito",
    "cardLast4": "0855",
    "periodType": "quinto_util",
    "installmentCount": 4,
    "status": "pendente"
  }'
```

O sistema vincula o cartão pelo final, aplica a regra do quinto dia útil e
cria as parcelas nos meses seguintes. O valor enviado é o total da compra;
as parcelas são divididas em centavos pelo servidor.

### Criar um cartão sem expor número completo

```bash
curl -X POST "https://financeiro.byethost32.com/api/v1.php/cards" \
  -H "Authorization: Bearer sf_live_sua_chave" \
  -H "Content-Type: application/json" \
  -d '{
    "nickname": "Santander principal",
    "institution": "Santander",
    "brand": "Mastercard",
    "bin4": "5100",
    "last4": "0855",
    "limitTotal": 24000,
    "cardholderName": "ELIANE ANACLETO NETO"
  }'
```

## Regras financeiras aplicadas

- Compra no crédito depois do quinto dia útil: a primeira parcela ou valor
  total vai para a competência do mês seguinte.
- Compra no crédito até o quinto dia útil: entra na competência atual.
- Parcelas são criadas automaticamente com `installmentNumber`,
  `installmentCount`, `installmentAmount` e `referenceMonth`.
- Pix, débito e dinheiro permanecem organizados pela data do pagamento.
- O Dia 20 não recebe descontos de holerite; descontos ficam nos campos
  próprios do quinto dia útil.
- Cartão é vinculado pelo `cardId` ou pelos quatro últimos dígitos.
- Toda consulta é filtrada pelo `user_id` dono da chave.

## Formato de resposta

Sucesso:

```json
{
  "ok": true,
  "data": {},
  "meta": {}
}
```

Erro:

```json
{
  "ok": false,
  "error": {
    "code": "invalid_request",
    "message": "Descrição do erro",
    "details": {}
  },
  "requestId": "identificador-para-suporte"
}
```

## CORS e limite

Por padrão a API aceita integrações de qualquer origem, mas continua exigindo
chave. Em produção, restrinja o CORS com a variável:

```text
SF_API_CORS_ORIGINS=https://erp.exemplo.com,https://app.exemplo.com
```

Cada chave tem limite padrão de 120 requisições por minuto, configurável entre
10 e 600 na criação.

## Instalação no WAMP e na hospedagem

1. Garanta PHP 8.1+ e `pdo_sqlite` no WAMP; na hospedagem, `pdo_mysql`.
2. Configure `config/config.local.php` ou variáveis `SF_DB_*` somente no
   servidor.
3. Mantenha `api/v1.php`, `api/developer-keys.php`, `api/bootstrap.php`,
   `api/finance.php` e `install/mysql-schema.sql` sincronizados.
4. Acesse `/api/v1.php/health`.
5. Crie a chave pelo endpoint administrativo.
6. Teste primeiro com uma chave `read`; só depois libere `write`.

O arquivo `api/v1-openapi.json` pode ser importado no Swagger UI, Postman,
Insomnia ou geradores de SDK.
