package br.com.brenocruz.controlefinanceiro;

import android.app.Activity;
import android.content.Context;
import android.content.SharedPreferences;
import android.graphics.Color;
import android.net.Uri;
import android.os.Bundle;
import android.view.Gravity;
import android.webkit.CookieManager;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.Button;
import android.widget.EditText;
import android.widget.LinearLayout;
import android.widget.TextView;
import android.widget.Toast;

public class MainActivity extends Activity {
    private static final String PREFS = "controle_financeiro_mobile";
    private static final String SERVER_URL = "server_url";
    private static final int GREEN = Color.rgb(11, 127, 105);
    private static final int TEXT = Color.rgb(23, 63, 70);
    private static final int MUTED = Color.rgb(83, 112, 109);
    private WebView webView;
    private SharedPreferences preferences;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        preferences = getSharedPreferences(PREFS, MODE_PRIVATE);
        String savedUrl = preferences.getString(SERVER_URL, "");
        if (savedUrl.isEmpty()) {
            showConfiguration();
        } else {
            showWeb(savedUrl);
        }
    }

    private int dp(float value) {
        return Math.round(value * getResources().getDisplayMetrics().density);
    }

    private TextView text(String value, float size, int color) {
        TextView view = new TextView(this);
        view.setText(value);
        view.setTextSize(size);
        view.setTextColor(color);
        return view;
    }

    private LinearLayout baseLayout() {
        LinearLayout layout = new LinearLayout(this);
        layout.setOrientation(LinearLayout.VERTICAL);
        layout.setPadding(dp(22), dp(18), dp(22), dp(18));
        layout.setBackgroundColor(Color.rgb(244, 248, 247));
        return layout;
    }

    private LinearLayout header(boolean showSettings) {
        LinearLayout bar = new LinearLayout(this);
        bar.setGravity(Gravity.CENTER_VERTICAL);
        TextView title = text("Controle Financeiro", 20, TEXT);
        title.setTypeface(null, android.graphics.Typeface.BOLD);
        bar.addView(title, new LinearLayout.LayoutParams(0, dp(48), 1));
        if (showSettings) {
            Button settings = new Button(this);
            settings.setText("Configurar");
            settings.setTextColor(GREEN);
            settings.setOnClickListener(view -> showConfiguration());
            bar.addView(settings, new LinearLayout.LayoutParams(dp(112), dp(48)));
        }
        return bar;
    }

    private void showConfiguration() {
        LinearLayout layout = baseLayout();
        layout.addView(header(false));

        TextView eyebrow = text("APLICATIVO MÓVEL", 12, GREEN);
        eyebrow.setTypeface(null, android.graphics.Typeface.BOLD);
        eyebrow.setPadding(0, dp(26), 0, dp(8));
        layout.addView(eyebrow);

        TextView title = text("Conecte ao seu Financeiro", 26, TEXT);
        title.setTypeface(null, android.graphics.Typeface.BOLD);
        layout.addView(title);

        TextView description = text("Informe o endereço do servidor PHP para abrir o mesmo sistema web no celular.", 15, MUTED);
        description.setPadding(0, dp(10), 0, dp(22));
        layout.addView(description);

        EditText urlInput = new EditText(this);
        urlInput.setSingleLine(true);
        urlInput.setText(preferences.getString(SERVER_URL, ""));
        urlInput.setHint("https://seu-dominio/sistema-financeiro/");
        urlInput.setInputType(android.text.InputType.TYPE_CLASS_TEXT | android.text.InputType.TYPE_TEXT_VARIATION_URI);
        layout.addView(urlInput, new LinearLayout.LayoutParams(-1, dp(58)));

        TextView note = text("Use HTTPS em produção. localhost só funciona no próprio computador; para testes no emulador Android use http://10.0.2.2/.", 13, MUTED);
        note.setPadding(0, dp(10), 0, dp(20));
        layout.addView(note);

        Button open = new Button(this);
        open.setText("Abrir sistema");
        open.setTextColor(Color.WHITE);
        open.setBackgroundColor(GREEN);
        open.setOnClickListener(view -> {
            String value = urlInput.getText().toString().trim();
            Uri uri = Uri.parse(value);
            if (!"http".equalsIgnoreCase(uri.getScheme()) && !"https".equalsIgnoreCase(uri.getScheme())) {
                Toast.makeText(this, "Informe uma URL iniciando com http:// ou https://", Toast.LENGTH_LONG).show();
                return;
            }
            if (!value.endsWith("/")) value += "/";
            preferences.edit().putString(SERVER_URL, value).apply();
            showWeb(value);
        });
        layout.addView(open, new LinearLayout.LayoutParams(-1, dp(52)));

        TextView credit = text("Desenvolvido por BRENO CRUZ · © 2026", 12, MUTED);
        credit.setGravity(Gravity.CENTER);
        LinearLayout.LayoutParams creditParams = new LinearLayout.LayoutParams(-1, dp(48));
        creditParams.gravity = Gravity.BOTTOM;
        creditParams.topMargin = dp(28);
        layout.addView(credit, creditParams);
        setContentView(layout);
    }

    private void showWeb(String url) {
        LinearLayout layout = baseLayout();
        layout.setPadding(0, 0, 0, 0);
        layout.addView(header(true));

        webView = new WebView(this);
        WebSettings settings = webView.getSettings();
        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setDatabaseEnabled(true);
        settings.setBuiltInZoomControls(false);
        settings.setDisplayZoomControls(false);
        settings.setMixedContentMode(WebSettings.MIXED_CONTENT_NEVER_ALLOW);
        CookieManager.getInstance().setAcceptCookie(true);
        CookieManager.getInstance().setAcceptThirdPartyCookies(webView, false);
        webView.setWebChromeClient(new WebChromeClient());
        webView.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
                return false;
            }
        });
        layout.addView(webView, new LinearLayout.LayoutParams(-1, 0, 1));
        setContentView(layout);
        webView.loadUrl(url);
    }

    @Override
    public void onBackPressed() {
        if (webView != null && webView.canGoBack()) {
            webView.goBack();
        } else {
            super.onBackPressed();
        }
    }
}
