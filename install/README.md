# Patch de instalacao

Este diretório contém apenas patches administrativos executados pelo terminal.
O `.htaccess` bloqueia o acesso pelo navegador.

O patch de 11/09/2026 apaga os dados de todos os usuários, remove as contas e
cria o administrador inicial `admin`. Execute somente depois de possuir um
backup do arquivo SQLite:

```text
php install/patch-20260911.php --reset-all --confirm=RESET-TODOS-DADOS --admin-password=admin
```

A senha inicial é temporária e deve ser trocada no primeiro acesso. Nunca
publique o comando com a senha em histórico compartilhado.

## Hospedagem com MySQL/MariaDB

1. Crie um banco MySQL/MariaDB e um usuário com permissão nesse banco no
   painel da hospedagem. A extensão PHP `pdo_mysql` precisa estar habilitada.
2. Copie `config/config.example.php` para `config/config.local.php` e preencha
   host, porta, banco, usuário e senha. Esse arquivo fica protegido e não deve
   ser publicado no Git.
3. Envie o projeto para o document root do domínio e mantenha o `.htaccess` da
   raiz. Ele remove a dependência do caminho local `sistema financeiro` e faz
   as rotas `api/auth` e `api/finance` funcionarem sem `.php`.
4. Abra o domínio em HTTPS. Teste `https://seu-dominio/api/health.php`. O primeiro acesso cria automaticamente as tabelas
   MySQL a partir de `mysql-schema.sql` e cria o administrador caso o banco
   esteja vazio.

O arquivo `data/financeiro.sqlite` é apenas o banco local de desenvolvimento.
Em produção, o driver deve ser `mysql`; não envie dumps SQL com dados pessoais
para uma pasta pública.

### Instalador não destrutivo

Depois de copiar os arquivos e configurar o MySQL, valide a hospedagem:

```text
php install/hosting-install.php --check
```

Para criar somente as tabelas que ainda não existirem e preservar os dados já
existentes:

```text
php install/hosting-install.php --prepare --base-url=https://seu-dominio.example/
```

O instalador funciona apenas pelo terminal e nunca executa `DROP`, `DELETE`,
`TRUNCATE` ou reset de usuários. Sem SSH, abra `api/health.php` pelo domínio;
o backend também prepara tabelas ausentes de forma aditiva no primeiro acesso.
