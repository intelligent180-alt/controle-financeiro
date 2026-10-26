# Versao movel

O sistema agora possui uma base PWA. Em um celular, abra a URL do sistema no
navegador e use "Adicionar a tela inicial" ou "Instalar aplicativo". O login e
as consultas continuam usando o servidor PHP, portanto o celular precisa
alcançar o endereço do WAMP pela rede.

## APK nativo

O projeto Android fica em `mobile/android`. Ele abre o mesmo sistema web em um
WebView e aponta automaticamente para a hospedagem oficial pelo domínio HTTPS
compilado no APK. O usuário não precisa digitar URL. O fluxo correto é:

```text
APK -> domínio/DNS HTTPS -> API PHP -> MySQL privado
```

O APK nunca acessa o MySQL diretamente. Assim, a senha do banco permanece no
servidor e web, APK e banco usam a mesma API e os mesmos dados. Não use
`localhost` no celular: ele aponta para o próprio aparelho.

Para gerar o APK oficial, basta compilar:

```text
gradlew.bat assembleDebug
```

O projeto ainda aceita `-PappServerUrl=...` somente no momento da compilação
para testes locais ou outra hospedagem; o usuário final não vê essa opção. O
APK também habilita a seleção de PNG, JPG e PDF pelo módulo de
importacao de comprovantes e holerites.

O backend deve continuar protegido por HTTPS, sessao e autenticacao. Nunca
coloque credenciais de APIs no aplicativo movel.

Para compilar com Android Studio, abra a pasta `mobile/android` e use **Build >
Build APK(s)**. Pelo terminal, use `gradlew.bat assembleDebug` depois de ter o
SDK Android 35 e o Gradle configurados.
