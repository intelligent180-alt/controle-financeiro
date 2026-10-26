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

### Backup antes e depois da integração

O arquivo `backup-system.php` cria um snapshot sem apagar ou alterar o banco
original. Ele copia os arquivos do sistema, omite credenciais e salva o banco
em uma subpasta com data e hora.

Para guardar o sistema local que ainda usa SQLite:

```text
php install/backup-system.php --source=sqlite
```

Depois que o sistema estiver integrado ao MySQL, faça o backup do banco
integrado pelo terminal disponível na hospedagem ou pelo phpMyAdmin. Se houver
SSH/terminal no ambiente, o mesmo arquivo pode gerar um dump MySQL:

```text
php install/backup-system.php --source=mysql
```

O backup não inclui `config/config.local.php`, pois ele contém a senha do
banco. Após uma restauração, recrie esse arquivo a partir de
`config/config.example.php`. Não execute novamente o
`install/patch-20260911.php`, pois ele é um patch antigo de reset.

### Migrar o usuario legado para o MySQL

Durante a instalação, o sistema pode localizar o SQLite antigo e mostrar uma
prévia do que será migrado para um CPF específico. A prévia não conecta nem
altera o MySQL:

```text
php install/hosting-install.php --migrate \
  --source=data/financeiro.sqlite \
  --cpf=SEU_CPF \
  --profile=clt
```

Depois de conferir as quantidades exibidas, execute com a confirmação explícita:

```text
php install/hosting-install.php --migrate \
  --source=data/financeiro.sqlite \
  --cpf=SEU_CPF \
  --profile=clt \
  --confirm=MIGRAR-DADOS-SEM-APAGAR
```

Essa etapa cria as tabelas ausentes, preserva o usuário e seus registros
relacionados e não apaga registros já existentes no MySQL. A opção
`--profile=clt` altera somente o perfil e as respostas de perfil do usuário
identificado; não remove movimentações, cartões ou recebimentos.

Se a prévia listar mais de um ID para o mesmo CPF, não execute ainda. Isso
indica contas antigas duplicadas na origem. Escolha o ID correto e repita a
prévia, por exemplo:

```text
php install/hosting-install.php --migrate \
  --source=backup/user-15416282681-historico/financeiro.sqlite \
  --cpf=SEU_CPF --source-user-id=2 --profile=clt
```

Somente depois confira os registros desse ID e acrescente a confirmação.

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
