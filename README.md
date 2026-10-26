# Controle Financeiro

Sistema web e mobile para organização financeira pessoal e profissional,
desenvolvido por **BRENO CRUZ**.

Versão atual: `1.0.8-api-rest`

## Visão do produto

O Controle Financeiro centraliza receitas, gastos, cartões, parcelas,
recebimentos, holerites, metas, extratos e recursos profissionais em uma única
aplicação. O sistema adapta o dashboard ao perfil identificado no primeiro
acesso: CLT, prestador PJ, comerciante PF e comerciante PJ.

## Arquitetura

```text
Painel Web ───────┐
APK Android ──────┼── HTTPS ── API PHP ── PDO ── MySQL/SQLite
Integrações REST ─┘                 │
                                    ├── Sessão do painel
                                    ├── API keys para parceiros
                                    └── OpenAPI / Swagger
```

O navegador e o APK nunca acessam o MySQL diretamente. O banco permanece no
servidor e todas as regras são aplicadas no backend.

## Principais funcionalidades

- Dashboard com disponibilidade, semáforo de gastos e economizômetro;
- movimentações por período, categoria, método e status;
- cartões com instituição, bandeira, titular, limite e últimos quatro dígitos;
- cálculo automático de parcelas e previsão mensal;
- importação inteligente de PNG, JPG e PDF;
- reconhecimento de estabelecimento, valor, data, hora, cartão e parcelas;
- Pix, débito, dinheiro e cartão separados;
- contas a pagar, recebimentos, holerites e metas;
- módulos profissionais para PJ e comerciantes;
- relatórios em PDF;
- APK Android conectado ao mesmo domínio;
- API REST pública para sistemas externos.

## Regras financeiras

1. Cartão até o quinto dia útil permanece na competência atual.
2. Cartão depois do quinto dia útil vai para o mês seguinte.
3. Parcelas são criadas no servidor com divisão exata em centavos.
4. O cartão é vinculado pelo `cardId` ou pelos quatro últimos dígitos.
5. Pix, débito e dinheiro são organizados pela data do pagamento.
6. Recebimentos do Dia 20 não sofrem descontos de holerite.
7. Cada registro é filtrado pelo `user_id`.

## API REST

Documentação visual: <https://financeiro.byethost32.com/docs/api.html><br>
Contrato OpenAPI: `api/v1-openapi.json`<br>
Documentação completa: `docs/API-REST.md`

```text
GET    /api/v1.php/health
GET    /api/v1.php/me
GET    /api/v1.php/dashboard?month=2026-11
GET    /api/v1.php/transactions?month=2026-11
POST   /api/v1.php/transactions
DELETE /api/v1.php/transactions/{id}
GET    /api/v1.php/cards
POST   /api/v1.php/cards
GET    /api/v1.php/incomes?month=2026-11
```

As integrações usam `Authorization: Bearer sf_live_sua_chave`. As chaves são
criadas pelo administrador, possuem escopos `read` e `write`, limite de
requisições e ficam armazenadas no banco somente como hash SHA-256.

## Banco e dados

O projeto suporta SQLite no desenvolvimento e MySQL/MariaDB na hospedagem,
usando PDO, prepared statements, migrations não destrutivas, backup e filtro
por usuário. Credenciais ficam somente em `config/config.local.php` ou em
variáveis de ambiente; esse arquivo é ignorado pelo Git.

## APK Android

O APK fica em `mobile/android` e usa Java, WebView, Gradle e Android SDK 35.
Ele abre o mesmo sistema web no domínio oficial, sem o usuário digitar URL.

```text
APK → HTTPS/DNS → API PHP → MySQL
```

Versão: `1.0.8`<br>
Compilação: `cd mobile/android && gradlew.bat assembleDebug`

## Open Finance

A arquitetura está preparada para receber dados de um provedor Open Finance,
sem armazenar senha bancária. O fluxo futuro será consentimento no Banco Inter,
provedor autorizado, webhook e classificação automática na API.

## Estrutura

```text
api/       backend PHP, autenticação, financeiro, REST e OpenAPI
config/    exemplos sem credenciais reais
css/       identidade visual responsiva
docs/      API, arquitetura e portfólio
install/   schema, instalação, backup e migração
js/        interface web
mobile/    projeto Android/WebView
backup/    versões controladas
data/      dados locais ignorados pelo Git
```

## Autor

**Breno Cruz** — Controle Financeiro, sistema web, mobile e API REST.
