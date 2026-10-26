# Versao movel

O sistema agora possui uma base PWA. Em um celular, abra a URL do sistema no
navegador e use "Adicionar a tela inicial" ou "Instalar aplicativo". O login e
as consultas continuam usando o servidor PHP, portanto o celular precisa
alcançar o endereço do WAMP pela rede.

## APK nativo

O projeto Android fica em `mobile/android`. Ele abre o mesmo sistema web em um
WebView e permite configurar o endereco do servidor na primeira execucao. Nao
use `localhost` no celular: ele aponta para o proprio aparelho.

Antes da compilacao, configure uma URL como:

```text
https://seu-dominio.example/sistema-financeiro/
```

O backend deve continuar protegido por HTTPS, sessao e autenticacao. Nunca
coloque credenciais de APIs no aplicativo movel.

Para compilar com Android Studio, abra a pasta `mobile/android` e use **Build >
Build APK(s)**. Pelo terminal, use `gradlew.bat assembleDebug` depois de ter o
SDK Android 35 e o Gradle configurados.
