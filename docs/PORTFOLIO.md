# Portfólio técnico — Controle Financeiro

## Resumo profissional

O Controle Financeiro é uma plataforma full stack para gestão financeira
pessoal e profissional. O projeto combina painel web responsivo, APK Android,
persistência SQLite/MySQL, importação inteligente de extratos e API REST
versionada para integração com outros produtos.

O foco técnico é transformar comprovantes e extratos não estruturados em
lançamentos organizados, auditáveis e vinculados ao usuário correto.

## Problema resolvido

O usuário normalmente tem informações espalhadas em banco, cartão, Pix,
holerite, PDFs e anotações. O sistema unifica essas fontes e mostra o que já
foi pago, o que está pendente e o que será pago nos próximos meses.

## Diferenciais

- regra financeira centralizada no backend;
- parcelamento com previsão mensal;
- associação de cartão por final de quatro dígitos;
- perfis financeiros definidos no primeiro acesso;
- layout responsivo para desktop e mobile;
- API pública com escopos e rate limit;
- documentação OpenAPI e Swagger;
- banco protegido atrás da API;
- compatibilidade com SQLite local e MySQL em produção.

## Tecnologias

### Backend

PHP 8.1+, PDO, sessões HTTP, JSON, prepared statements, MySQL/MariaDB e
SQLite.

### Frontend

HTML5, CSS3, JavaScript vanilla, SVG, PWA e Service Worker.

### Mobile

Java Android, WebView, Gradle, Android SDK 35 e seletor de arquivos para PNG,
JPG e PDF.

### Integrações

REST, Bearer API keys, CORS, OpenAPI 3.0, Swagger UI e futura conexão Open
Finance por provedor regulado.

## Fluxo de uma compra parcelada

```text
Sistema externo envia compra
          ↓
API valida valor, data e cartão
          ↓
Backend identifica o quinto dia útil
          ↓
Competência é calculada
          ↓
Parcelas são divididas em centavos
          ↓
Cada mês recebe seu lançamento
          ↓
Dashboard e contas a pagar são atualizados
```

## Fluxo de autenticação

O painel usa sessão PHP `SF_AUTH`. Integrações externas usam uma chave Bearer
criada pelo administrador. O servidor calcula o hash, encontra o usuário dono
e adiciona `user_id` a todas as consultas.

## Como apresentar em uma entrevista

> Desenvolvi uma plataforma financeira web e mobile em PHP, JavaScript e
> Android, com suporte a SQLite e MySQL. Centralizei a regra de parcelamento e
> competência no backend, criei importação de extratos e disponibilizei uma API
> REST versionada com API keys, escopos, rate limit e documentação OpenAPI.
> O APK usa o mesmo backend por HTTPS, então navegador, aplicativo e sistemas
> externos permanecem sincronizados.

## Próximas evoluções

- adaptador Pluggy/Belvo para Open Finance;
- webhooks bancários com deduplicação por identificador externo;
- fila de sincronização e retentativas;
- testes automatizados de regras financeiras;
- observabilidade com logs e métricas;
- assinatura de release Android;
- painel administrativo para criar e revogar chaves visualmente.
