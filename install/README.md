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
