# Versao movel

O sistema agora possui uma base PWA. Em um celular, abra a URL do sistema no
navegador e use "Adicionar a tela inicial" ou "Instalar aplicativo". O login e
as consultas continuam usando o servidor PHP, portanto o celular precisa
alcançar o endereço do WAMP pela rede.

## APK nativo

O APK ainda nao foi compilado neste ambiente porque Android SDK, Java/Gradle e
um endereco HTTPS acessivel pelo celular nao estao instalados/configurados.
Para gerar o APK com seguranca, a proxima etapa e empacotar esta aplicacao em
um WebView/Capacitor apontando para a URL publicada do sistema. Nao use
`localhost` no celular: ele aponta para o proprio aparelho.

Antes da compilacao, configure uma URL como:

```text
https://seu-dominio.example/sistema-financeiro/
```

O backend deve continuar protegido por HTTPS, sessao e autenticacao. Nunca
coloque credenciais de APIs no aplicativo movel.
