# Versao movel

O sistema agora possui uma base PWA. Em um celular, abra a URL do sistema no
navegador e use "Adicionar a tela inicial" ou "Instalar aplicativo". O login e
as consultas continuam usando o servidor PHP, portanto o celular precisa
alcançar o endereço do WAMP pela rede.

## APK nativo

O projeto Android fica em `mobile/android`. Ele abre o mesmo sistema web em um
WebView e ja aponta automaticamente para a hospedagem oficial. O usuario nao
precisa digitar URL. Nao use `localhost` no celular: ele aponta para o proprio
aparelho.

Para gerar o APK oficial, basta compilar:

```text
gradlew.bat assembleDebug
```

O projeto ainda aceita `-PappServerUrl=...` somente para testes locais ou outra
hospedagem. O APK tambem habilita a selecao de PNG, JPG e PDF pelo modulo de
importacao de comprovantes e holerites.

O backend deve continuar protegido por HTTPS, sessao e autenticacao. Nunca
coloque credenciais de APIs no aplicativo movel.

Para compilar com Android Studio, abra a pasta `mobile/android` e use **Build >
Build APK(s)**. Pelo terminal, use `gradlew.bat assembleDebug` depois de ter o
SDK Android 35 e o Gradle configurados.
