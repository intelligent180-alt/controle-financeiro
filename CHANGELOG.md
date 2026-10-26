# Historico de versoes

## 1.0.7-piloto - 26/10/2026

- APK passa a abrir automaticamente a hospedagem oficial sem pedir URL ao usuario.
- Selecao de arquivos PNG, JPG e PDF habilitada no WebView para comprovantes e holerites.
- APK compilado com versao 1.0.7 e acesso seguro somente por HTTPS.

## 1.0.6-piloto - 26/10/2026

- Instalador não destrutivo para preparar hospedagem, banco MySQL e vínculo do domínio com o APK.
- Validação de arquivos web/mobile e preservação explícita de usuários e movimentações existentes.

## 1.0.5-piloto - 26/10/2026

- APIs do frontend passaram a usar os arquivos `.php` diretamente, sem depender de `mod_rewrite`.
- Respostas HTML de erro agora exibem uma mensagem clara em vez de falha de JSON.
- Cache do PWA atualizado para impedir que o navegador mantenha o JavaScript antigo.

## 1.0.4-piloto - 26/10/2026

- Backend preparado para SQLite local ou MySQL/MariaDB em hospedagem.
- Configuração de banco separada em `config/config.local.php` ou variáveis de ambiente.
- Schema MySQL automático e endpoint de diagnóstico em `api/health`.
- Removido redirecionamento fixo para a pasta local do WAMP.
- APK preparado para abrir um domínio configurado no build ou informado na primeira abertura.

## 1.0.3-piloto - 11/09/2026

- Cadastro de novos usuários protegido por autorização administrativa.
- Usuário público não pode mais criar contas diretamente.
- Tela administrativa de gestão de acessos adicionada.
- APK híbrido configurável preparado para o mesmo backend web.

## 1.0.2-piloto - 11/09/2026

- Patch de instalacao criado para inicializar o sistema com administrador.
- Reset global executado apos backup recuperavel do banco.
- Login administrativo por usuario e troca obrigatoria da senha inicial.

## 1.0.1-piloto - 11/09/2026

- Identificacao visual do desenvolvedor adicionada ao login e ao sistema.

## 1.0.0-piloto - 11/09/2026

- Primeiro marco versionado do sistema financeiro.
- Base preparada para instalacao como aplicativo movel (PWA).
- Manifesto, icone e service worker adicionados sem alterar o fluxo web ou as APIs.
- Dados locais e credenciais protegidos pelo `.gitignore`.
