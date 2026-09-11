const state = {
    user: null,
    month: '',
    dashboard: null,
    pendingImport: [],
    tesseractLoading: null,
    pdfLoading: null,
};

const $ = id => document.getElementById(id);

function todayIso() {
    return new Date().toISOString().slice(0, 10);
}

function currentMonth() {
    return new Date().toISOString().slice(0, 7);
}

function money(value) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value || 0));
}

function parseMoney(value) {
    const text = String(value || '').trim();
    if (!text) return 0;
    const normalized = text.includes(',')
        ? text.replace(/\./g, '').replace(',', '.').replace(/[^\d.-]/g, '')
        : text.replace(/[^\d.-]/g, '');
    return Number(normalized || 0);
}

function html(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[char]));
}

function dateBr(value) {
    if (!value) return '--';
    const [year, month, day] = String(value).split('-');
    return day && month && year ? `${day}/${month}/${year}` : value;
}

function monthLabel(value) {
    if (!value) return '--';
    const [year, month] = String(value).split('-').map(Number);
    return new Date(year, month - 1, 1).toLocaleDateString('pt-BR', { month: 'short', year: 'numeric' });
}

async function api(path, options = {}) {
    const response = await fetch(path, {
        headers: {
            'Content-Type': 'application/json',
            ...(options.headers || {}),
        },
        ...options,
    });
    const text = await response.text();
    let data = {};
    if (text.trim()) {
        data = JSON.parse(text);
    }
    if (!response.ok || data.ok === false) {
        throw new Error(data.message || 'Falha na API.');
    }
    return data;
}

async function financePost(action, payload = {}) {
    return api('api/finance', {
        method: 'POST',
        body: JSON.stringify({ action, month: state.month, ...payload }),
    });
}

async function requireAuth() {
    const data = await api('api/auth');
    if (!data.authenticated) {
        window.location.href = 'login';
        return false;
    }
    state.user = data.user;
    $('welcomeText').textContent = `${data.user.name}, controle pessoal com periodos dia 20 e 5o dia util.`;
    return true;
}

async function logout() {
    await api('api/auth', {
        method: 'POST',
        body: JSON.stringify({ action: 'logout' }),
    }).catch(() => {});
    window.location.href = 'login';
}

async function loadDashboard() {
    state.month = $('monthFilter').value || currentMonth();
    if ($('payslipMonth')) $('payslipMonth').value = state.month;
    const data = await api(`api/finance?month=${encodeURIComponent(state.month)}`);
    state.dashboard = data;
    renderDashboard();
    await loadYearCalendar();
}

async function loadYearCalendar() {
    const year = Number((state.month || currentMonth()).slice(0, 4));
    const data = await api(`api/finance?action=calendar&year=${year}`);
    $('yearCalendar').innerHTML = (data.calendar || []).map(item => {
        const p20 = item.periods.find(p => p.type === 'dia20');
        const p5 = item.periods.find(p => p.type === 'quinto_util');
        return `
            <div class="calendar-month">
                <strong>${html(monthLabel(item.month))}</strong>
                <span>Dia 20: ${dateBr(p20?.dueDate)}</span>
                <span>5o util: ${dateBr(p5?.dueDate)}</span>
            </div>
        `;
    }).join('');
}

function renderDashboard() {
    const data = state.dashboard || {};
    const summary = data.summary || {};
    $('availableBalance').textContent = money(summary.availableBalance);
    $('incomeTotal').textContent = money(summary.income);
    $('expenseTotal').textContent = money(summary.expenses);
    $('pendingTotal').textContent = money(summary.pending);
    $('savedTotal').textContent = money(summary.savedGoals);
    $('balanceNote').textContent = `${summary.usagePercent || 0}% da renda liquida usada | ${summary.savingPercent || 0}% reservado.`;
    $('calendarNote').textContent = `Competencia ${monthLabel(data.month)}: dia 20 e 5o dia util do mes seguinte.`;

    renderPeriods(data);
    renderCategoryChart(data);
    renderBalanceLimitCharts(data);
    renderDashboardCards(data.cards || [], data.cardSummary || []);
    renderTransactions(data.transactions || []);
    renderIncomes(data.incomes || []);
    renderPayslips(data.incomes || []);
    renderCards(data.cards || [], data.cardSummary || []);
    renderGoals(data.goals || []);
    renderAssistantBase(data);
}

function renderPeriods(data) {
    const periods = data.periods || {};
    const calendar = data.calendar || [];
    $('periodCards').innerHTML = calendar.map(period => {
        const totals = periods[period.type] || {};
        return `
            <article class="soft-card period-card">
                <span class="stat-label">${html(period.label)}</span>
                <strong>${dateBr(period.dueDate)}</strong>
                <p class="muted">${html(period.description)}</p>
                <div class="mini-ledger">
                    <span>Recebi <b>${money(totals.income)}</b></span>
                    <span>Usei <b>${money(totals.expenses)}</b></span>
                    <span>Pendente <b>${money(totals.pending)}</b></span>
                </div>
            </article>
        `;
    }).join('');
}

function renderCategoryChart(data) {
    const categories = data.categories || [];
    if (!categories.length) {
        $('categoryChart').innerHTML = '<p class="muted">Nenhum gasto categorizado ainda.</p>';
        return;
    }
    const max = Math.max(...categories.map(item => Number(item.amount || 0)), 1);
    $('categoryChart').innerHTML = categories.slice(0, 8).map(item => {
        const width = Math.max(4, Math.round((Number(item.amount || 0) / max) * 100));
        const visual = categoryVisual(item.name);
        return `
            <div class="bar-row">
                <span class="category-name"><span class="category-picture category-picture-${visual.className}" role="img" aria-label="${html(visual.label)}">${visual.icon}</span><strong>${html(item.name)}</strong></span>
                <span class="bar-track"><span class="bar-fill" style="width:${width}%"></span></span>
                <span>${money(item.amount)}</span>
            </div>
        `;
    }).join('');
}

function categoryVisual(category) {
    const key = String(category || 'Outros')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
    const visuals = [
        ['pensao', '⚖️', 'Pensao', 'pensao'],
        ['mercado', '🛒', 'Mercado', 'mercado'],
        ['transporte', '🚗', 'Transporte', 'transporte'],
        ['saude', '❤️', 'Saude', 'saude'],
        ['alimentacao', '🍽️', 'Alimentacao', 'alimentacao'],
        ['moradia', '🏠', 'Moradia', 'moradia'],
        ['vestuario', '👕', 'Vestuario', 'vestuario'],
        ['eletronicos', '📱', 'Eletronicos', 'eletronicos'],
        ['educacao', '📚', 'Educacao', 'educacao'],
        ['lazer', '🎬', 'Lazer', 'lazer'],
        ['assinaturas', '🎧', 'Assinaturas', 'assinaturas'],
        ['pets', '🐾', 'Pets', 'pets'],
        ['beleza', '💄', 'Beleza', 'beleza'],
        ['cartao', '💳', 'Cartao', 'cartao'],
        ['receita', '💰', 'Receita', 'receita'],
    ];
    const found = visuals.find(([term]) => key.includes(term));
    return found
        ? { icon: found[1], label: found[2], className: found[3] }
        : { icon: '📦', label: 'Outros', className: 'outros' };
}

function cardBrandFromBin4(value) {
    const bin4 = String(value || '').replace(/\D/g, '').slice(0, 4);
    if (bin4.length < 4) return '';
    const prefix = Number(bin4);
    if (['4389', '4573', '4576', '5041', '6277', '6362', '6363'].includes(bin4) || (prefix >= 6500 && prefix <= 6509) || prefix === 6516 || (prefix >= 6550 && prefix <= 6559)) return 'Elo';
    if (bin4 === '6062' || bin4 === '3841') return 'Hipercard';
    if (/^3[47]/.test(bin4)) return 'American Express';
    if ((prefix >= 5100 && prefix <= 5599) || (prefix >= 2221 && prefix <= 2720)) return 'Mastercard';
    if (prefix >= 3528 && prefix <= 3589) return 'JCB';
    if (bin4 === '6011' || (prefix >= 6440 && prefix <= 6499)) return 'Discover';
    if (bin4.startsWith('4')) return 'Visa';
    if (prefix >= 6200 && prefix <= 6299) return 'UnionPay';
    if ((prefix >= 3000 && prefix <= 3059) || (prefix >= 3600 && prefix <= 3699)) return 'Diners Club';
    return '';
}

function cardVisualMeta(card) {
    const detectedBrand = cardBrandFromBin4(card.bin4 || '');
    const normalizeCardText = value => String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
    const institutionSource = normalizeCardText(card.institution);
    const fallbackInstitutionSource = normalizeCardText(`${card.productName || ''} ${card.nickname || ''}`);
    const brandSource = normalizeCardText(card.brand || detectedBrand);
    const institutions = [
        ['santander', 'Santander', 'theme-santander', 'S'],
        ['caixa', 'Caixa', 'theme-caixa', 'CEF'],
        ['itau', 'Itaú', 'theme-itau', 'itaú'],
        ['bradesco', 'Bradesco', 'theme-bradesco', 'b'],
        ['nubank', 'Nubank', 'theme-nubank', 'nu'],
        ['inter', 'Inter', 'theme-inter', 'inter'],
        ['banco do brasil', 'Banco do Brasil', 'theme-bb', 'BB'],
        ['bb', 'Banco do Brasil', 'theme-bb', 'BB'],
        ['c6', 'C6 Bank', 'theme-c6', 'C6'],
        ['pan', 'Banco PAN', 'theme-pan', 'PAN'],
        ['sicredi', 'Sicredi', 'theme-generic', 'SIC'],
        ['safra', 'Safra', 'theme-generic', 'SAF'],
        ['mercado pago', 'Mercado Pago', 'theme-generic', 'MP'],
        ['pagbank', 'PagBank', 'theme-generic', 'PAG'],
        ['picpay', 'PicPay', 'theme-generic', 'PIC'],
        ['will bank', 'Will Bank', 'theme-generic', 'W'],
    ];
    const networks = [
        ['mastercard', 'Mastercard', 'theme-mastercard', 'network-mastercard'],
        ['visa', 'Visa', 'theme-visa', 'network-visa'],
        ['elo', 'Elo', 'theme-elo', 'network-elo'],
        ['amex', 'American Express', 'theme-amex', 'network-amex'],
        ['hipercard', 'Hipercard', 'theme-hipercard', 'network-hipercard'],
        ['jcb', 'JCB', 'theme-generic', 'network-jcb'],
        ['discover', 'Discover', 'theme-generic', 'network-discover'],
        ['unionpay', 'UnionPay', 'theme-generic', 'network-unionpay'],
        ['diners', 'Diners Club', 'theme-generic', 'network-diners'],
    ];
    const institution = institutions.find(([term]) => institutionSource.includes(term))
        || institutions.find(([term]) => fallbackInstitutionSource.includes(term));
    const network = networks.find(([term]) => brandSource.includes(term))
        || networks.find(([, label]) => detectedBrand.toLowerCase() === label.toLowerCase());
    const institutionLabel = card.institution || institution?.[1] || 'Cartao';
    const networkLabel = network?.[1] || card.brand || detectedBrand || 'Cartao';
    return {
        institution: html(institutionLabel),
        issuerMark: html(institution?.[3] || institutionLabel.slice(0, 3).toUpperCase()),
        product: html(card.productName || card.nickname || 'Cartao'),
        network: html(networkLabel),
        networkClass: network?.[3] || 'network-generic',
        networkMark: html(network?.[1] || networkLabel),
        theme: institution?.[2] || network?.[2] || 'theme-generic',
    };
}

function renderCardVisual(card, extraClass = '') {
    const visual = cardVisualMeta(card);
    const holderName = html(card.cardholderName || state.user?.name || 'TITULAR DO CARTAO');
    const expiryDate = html(card.expiryDate || '--/--');
    return `
        <div class="credit-card-visual ${visual.theme} ${extraClass}">
            <div class="credit-card-sheen"></div>
            <div class="credit-card-top">
                <div class="credit-card-issuer-lockup"><span class="credit-card-issuer-mark">${visual.issuerMark}</span><span class="credit-card-issuer-details"><span class="credit-card-issuer-name">${visual.institution}</span><span class="credit-card-product">${visual.product}</span></span></div>
                <span class="credit-card-network ${visual.networkClass}"><span class="credit-card-network-symbol">${visual.networkMark}</span></span>
            </div>
            <div class="credit-card-chip-row"><div class="credit-card-chip"></div><span class="credit-card-contactless" aria-hidden="true">)))</span></div>
            <div class="credit-card-number">•••• &nbsp;•••• &nbsp;•••• &nbsp;${html(card.last4 || '----')}</div>
            <div class="credit-card-expiry"><span>VALIDADE</span><strong>${expiryDate}</strong></div>
            <div class="credit-card-bottom"><span>${holderName}</span><span>${visual.network}</span></div>
        </div>
    `;
}

function updateCardBrandDetection() {
    const digits = String($('cardNumber').value || '').replace(/\D/g, '');
    const bin4 = digits.length >= 8 ? digits.slice(0, 4) : String($('cardBin4').value || '').replace(/\D/g, '').slice(0, 4);
    $('cardBin4').value = bin4;
    if (digits.length >= 8 || bin4.length === 4) {
        const detected = cardBrandFromBin4(bin4);
        if (detected) {
            $('cardBrand').value = detected;
            $('cardDetectionNote').textContent = `Bandeira detectada automaticamente: ${detected} pelos 4 primeiros digitos.`;
        } else {
            $('cardDetectionNote').textContent = 'Os 4 primeiros digitos foram guardados para nova identificacao; confira a bandeira.';
        }
        return;
    }
    $('cardDetectionNote').textContent = bin4
        ? `Bandeira salva: ${$('cardBrand').value || 'confira o campo acima'}. Para detectar novamente, informe o numero completo.`
        : 'Digite o numero completo para identificar a bandeira pelos 4 primeiros digitos.';
}

function renderBalanceLimitCharts(data) {
    const summary = data.summary || {};
    const bars = [
        { label: 'Saldo disponivel', value: Number(summary.availableBalance || 0), className: 'balance' },
        { label: 'Gastos do periodo', value: Number(summary.expenses || 0), className: 'expenses' },
        { label: 'Limite usado', value: Number(summary.cardUsedTotal || 0), className: 'credit' },
    ];
    const max = Math.max(...bars.map(item => Math.abs(item.value)), 1);
    $('balanceLimitChart').innerHTML = bars.map(item => {
        const width = item.value === 0 ? 0 : Math.max(3, Math.min(100, Math.round((Math.abs(item.value) / max) * 100)));
        const negative = item.value < 0 ? ' is-negative' : '';
        return `
            <div class="finance-bar-row">
                <div class="finance-bar-label"><span>${item.label}</span><strong>${money(item.value)}</strong></div>
                <div class="finance-bar-track"><span class="finance-bar-fill ${item.className}${negative}" style="width:${width}%"></span></div>
            </div>
        `;
    }).join('');
    $('balanceLimitChartNote').textContent = `Competencia ${monthLabel(data.month)} | Cartoes: ${money(summary.cardUsedTotal || 0)} usados de ${money(summary.cardLimitTotal || 0)} (${Number(summary.cardUsagePercent || 0).toLocaleString('pt-BR')}%).`;

    const cards = data.cardSummary || [];
    if (!cards.length) {
        $('cardLimitChart').innerHTML = '<p class="muted">Cadastre um cartao para acompanhar o uso do limite.</p>';
        return;
    }
    $('cardLimitChart').innerHTML = cards.map(card => {
        const limit = Number(card.limitTotal || 0);
        const used = Number(card.used || 0);
        const available = Number(card.available ?? Math.max(0, limit - used));
        const percent = limit > 0 ? (used / limit) * 100 : 0;
        const width = limit > 0 ? Math.min(100, Math.max(0, Math.round(percent))) : 0;
        const over = percent > 100 ? ' is-over' : '';
        return `
            <div class="limit-card-row">
                <div class="limit-card-header"><span>${html(card.nickname || 'Cartao')} final ${html(card.last4 || '----')}</span><strong>${Number(percent).toLocaleString('pt-BR', { maximumFractionDigits: 1 })}%</strong></div>
                <div class="limit-track"><span class="limit-fill${over}" style="width:${width}%"></span></div>
                <div class="limit-card-foot"><span>Usado ${money(used)}</span><span>Disponivel ${money(available)} de ${money(limit)}</span></div>
            </div>
        `;
    }).join('');
}

function renderDashboardCards(cards, summary) {
    const container = $('dashboardCardVisuals');
    if (!container) return;
    const byId = Object.fromEntries((summary || []).map(item => [Number(item.id), item]));
    container.innerHTML = cards.map(card => {
        const totals = byId[Number(card.id)] || {};
        return `
            <article class="dashboard-card-tile">
                ${renderCardVisual(card, 'dashboard-card-image')}
                <div class="dashboard-card-info">
                    <div class="list-row"><strong>${html(card.nickname || 'Cartao')}</strong><b>${money(totals.available ?? card.limitTotal)}</b></div>
                    <span class="muted">${html(card.institution || 'Banco emissor')} | ${html(card.productName || 'Modelo nao informado')} | ${html(card.brand || cardBrandFromBin4(card.bin4) || 'Bandeira nao identificada')} | final ${html(card.last4 || '----')}</span>
                    <span class="muted">Usado ${money(totals.used || 0)} de ${money(card.limitTotal || 0)}</span>
                </div>
            </article>
        `;
    }).join('') || '<p class="muted">Cadastre um cartao para visualizar a imagem e acompanhar os lancamentos.</p>';
}

function renderTransactions(items) {
    $('transactionList').innerHTML = items.map(item => {
        const badgeClass = item.status === 'pago' ? 'success' : item.status === 'pendente' ? 'danger' : 'warn';
        const category = item.category || 'Outros';
        const visual = categoryVisual(category);
        const installmentInfo = Number(item.installmentCount || 1) > 1
            ? `<div class="installment-summary">Parcela ${Number(item.installmentNumber || 1)}/${Number(item.installmentCount)}: ${money(item.installmentAmount || item.amount)} por mes</div>`
            : '';
        const productDetails = item.notes && (item.notes.includes('Produtos identificados') || item.notes.includes('Comprovante com produtos'))
            ? `<div class="transaction-details">${html(item.notes)}</div>`
            : '';
        return `
            <article class="list-item">
                <div class="list-row">
                    <strong>${html(item.description)}</strong>
                    <b>${money(item.amount)}</b>
                </div>
                <div class="list-row">
                    <span class="muted transaction-category"><span class="category-picture category-picture-small category-picture-${visual.className}" role="img" aria-label="${html(visual.label)}">${visual.icon}</span><span>${dateBr(item.movementDate)}${item.movementTime ? ' as ' + html(item.movementTime) : ''} | ${html(category)} | ${periodText(item.periodType)}</span></span>
                    <span class="badge ${badgeClass}">${html(item.status)}</span>
                </div>
                <div class="list-row">
                    <span class="muted">${html(item.paymentMethod)} ${item.cardNickname ? '| ' + html(item.cardNickname) + (item.cardLast4 ? ' final ' + html(item.cardLast4) : '') : ''}</span>
                    <span>
                        <button class="tiny-btn" type="button" onclick="editTransaction(${Number(item.id)})">Editar</button>
                        <button class="tiny-btn" type="button" onclick="deleteTransaction(${Number(item.id)})">Excluir</button>
                    </span>
                </div>
                ${installmentInfo}
                ${productDetails}
            </article>
        `;
    }).join('') || '<p class="muted">Nenhum movimento no mes.</p>';

    const cardSelect = $('transactionCard');
    cardSelect.innerHTML = '<option value="">Sem cartao</option>' + (state.dashboard.cards || []).map(card =>
        `<option value="${Number(card.id)}">${html(card.nickname)} final ${html(card.last4 || '----')}</option>`
    ).join('');
}

function periodText(value) {
    return value === 'quinto_util' ? '5o dia util' : 'Dia 20';
}

function isCardTransactionForm() {
    const type = $('transactionType').value;
    const method = $('transactionMethod').value;
    return type === 'cartao_credito' || (method === 'credito' && type !== 'fatura_cartao') || Boolean($('transactionCard').value);
}

function isCreditPurchaseForm() {
    const type = $('transactionType').value;
    const method = $('transactionMethod').value;
    return type !== 'fatura_cartao' && (type === 'cartao_credito' || method === 'credito' || Boolean($('transactionCard').value));
}

function shiftIsoMonth(value, offset) {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(value || ''));
    if (!match) return value;
    const year = Number(match[1]);
    const month = Number(match[2]);
    const day = Number(match[3]);
    const target = new Date(Date.UTC(year, month - 1, 1));
    target.setUTCMonth(target.getUTCMonth() + offset);
    const lastDay = new Date(Date.UTC(target.getUTCFullYear(), target.getUTCMonth() + 1, 0)).getUTCDate();
    target.setUTCDate(Math.min(day, lastDay));
    return target.toISOString().slice(0, 10);
}

function easterDateUtc(year) {
    const a = year % 19;
    const b = Math.floor(year / 100);
    const c = year % 100;
    const d = Math.floor(b / 4);
    const e = b % 4;
    const f = Math.floor((b + 8) / 25);
    const g = Math.floor((b - f + 1) / 3);
    const h = (19 * a + b - d - g + 15) % 30;
    const i = Math.floor(c / 4);
    const k = c % 4;
    const l = (32 + 2 * e + 2 * i - h - k) % 7;
    const m = Math.floor((a + 11 * h + 22 * l) / 451);
    const month = Math.floor((h + l - 7 * m + 114) / 31) - 1;
    const day = ((h + l - 7 * m + 114) % 31) + 1;
    return new Date(Date.UTC(year, month, day));
}

function nationalHolidayIsoSet(year) {
    const fixed = [
        `${year}-01-01`, `${year}-04-21`, `${year}-05-01`, `${year}-09-07`,
        `${year}-10-12`, `${year}-11-02`, `${year}-11-15`, `${year}-11-20`, `${year}-12-25`,
    ];
    const easter = easterDateUtc(year);
    const movable = [-48, -47, -2, 60].map(offset => {
        const date = new Date(easter);
        date.setUTCDate(date.getUTCDate() + offset);
        return date.toISOString().slice(0, 10);
    });
    return new Set([...fixed, ...movable]);
}

function fifthBusinessDayIso(value) {
    const match = /^(\d{4})-(\d{2})-\d{2}$/.exec(String(value || ''));
    if (!match) return '';
    const year = Number(match[1]);
    const date = new Date(Date.UTC(year, Number(match[2]) - 1, 1));
    const holidays = nationalHolidayIsoSet(year);
    let businessDays = 0;
    while (businessDays < 5) {
        const weekday = date.getUTCDay();
        const iso = date.toISOString().slice(0, 10);
        if (weekday !== 0 && weekday !== 6 && !holidays.has(iso)) businessDays++;
        if (businessDays === 5) return date.toISOString().slice(0, 10);
        date.setUTCDate(date.getUTCDate() + 1);
    }
    return '';
}

function creditPurchaseAfterCutoff(value) {
    const cutoff = fifthBusinessDayIso(value);
    return Boolean(cutoff && String(value) > cutoff);
}

function updateCreditScheduleNote() {
    const note = $('transactionCreditRuleNote');
    if (!note) return;
    if (!isCreditPurchaseForm()) {
        note.textContent = 'Pix, debito e dinheiro entram no mes atual. No cartao, ate o 5o dia util fica no mes atual; depois dele, a primeira parcela ou o valor total vai para o mes seguinte.';
        return;
    }

    const date = $('transactionDate').value || todayIso();
    const cutoff = fifthBusinessDayIso(date);
    if (creditPurchaseAfterCutoff(date)) {
        const scheduledDate = shiftIsoMonth(date, 1);
        note.textContent = `Compra no credito feita em ${dateBr(date)}, apos o 5o dia util (${dateBr(cutoff)}): a primeira parcela ou o valor total sera lancado em ${dateBr(scheduledDate)}. As demais parcelas seguem nos meses seguintes.`;
    } else {
        note.textContent = `Compra no credito feita em ${dateBr(date)} entra na competencia atual ate o 5o dia util (${dateBr(cutoff)}). Depois dele, vai para o mesmo dia do mes seguinte.`;
    }
}

function calculateInstallmentAmount() {
    const amount = parseMoney($('transactionAmount').value);
    const count = Math.max(1, Math.min(60, Number($('transactionInstallments').value || 1)));
    $('transactionInstallments').value = count;
    $('transactionInstallmentAmount').value = money(amount > 0 ? amount / count : 0);
}

function updateInstallmentFields() {
    const cardPurchase = isCardTransactionForm();
    $('transactionInstallmentFields').hidden = false;
    $('transactionInstallments').disabled = !cardPurchase;
    if (!cardPurchase) $('transactionInstallments').value = 1;
    $('transactionInstallmentNote').textContent = cardPurchase
        ? 'Ao salvar, as parcelas futuras serao lancadas nos proximos meses.'
        : 'Para parcelar, selecione Compra no cartao e informe o total de parcelas.';
    calculateInstallmentAmount();
}

function clearTransactionForm() {
    $('transactionForm').reset();
    $('transactionId').value = '';
    $('transactionDate').value = todayIso();
    $('transactionTime').value = '';
    $('transactionPeriod').value = 'dia20';
    $('transactionInstallments').value = 1;
    updateInstallmentFields();
    updateCreditScheduleNote();
}

function editTransaction(id) {
    const item = (state.dashboard.transactions || []).find(row => Number(row.id) === Number(id));
    if (!item) return;
    $('transactionId').value = item.id;
    $('transactionType').value = item.type;
    $('transactionPeriod').value = item.periodType;
    $('transactionDate').value = item.movementDate;
    $('transactionTime').value = item.movementTime || '';
    $('transactionAmount').value = String(item.amount).replace('.', ',');
    $('transactionDescription').value = item.description;
    $('transactionCategory').value = item.category;
    $('transactionMethod').value = item.paymentMethod;
    $('transactionStatus').value = item.status;
    $('transactionCard').value = item.cardId || '';
    $('transactionInstallments').value = item.installmentCount || 1;
    $('transactionNotes').value = item.notes || '';
    updateInstallmentFields();
    activateTab('movements');
}

async function deleteTransaction(id) {
    if (!confirm('Excluir este lancamento?')) return;
    state.dashboard = await financePost('delete_transaction', { id });
    renderDashboard();
}

async function deleteIncome(id) {
    if (!confirm('Excluir este recebimento?')) return;
    state.dashboard = await financePost('delete_income', { id });
    renderDashboard();
}

async function resetFinancialData() {
    const confirmed = confirm('Isso vai excluir todos os lancamentos, recebimentos, cartoes, metas e importacoes desta conta. A conta de acesso sera preservada. Deseja continuar?');
    if (!confirmed) return;
    const typed = prompt('Para confirmar, digite ZERAR');
    if (typed !== 'ZERAR') return;
    try {
        state.dashboard = await financePost('reset_finances', { confirm: true });
        state.pendingImport = [];
        renderImportPreview();
        renderDashboard();
        alert('Dados financeiros zerados com sucesso.');
    } catch (error) {
        alert(error.message || 'Nao foi possivel zerar os dados financeiros.');
    }
}

function renderIncomes(items) {
    $('incomeList').innerHTML = items.map(item => {
        const isPayslip = item.payload?.source === 'holerite';
        const detail = isPayslip
            ? 'Holerite | ganho liquido informado diretamente'
            : `Bruto ${money(item.grossSalary)} | INSS ${money(item.inssDiscount)} | VT ${money(item.transportDiscount)}`;
        return `
            <article class="list-item">
                <div class="list-row"><strong>${periodText(item.periodType)}</strong><b>${money(item.netAmount)}</b></div>
                <span class="muted">Recebido em ${dateBr(item.paymentDate)} | ${detail}</span>
                <div class="list-row"><span></span><button class="tiny-btn" type="button" onclick="deleteIncome(${Number(item.id)})">Excluir</button></div>
            </article>
        `;
    }).join('') || '<p class="muted">Nenhum recebimento registrado.</p>';
}

function renderPayslips(items) {
    const payslips = items.filter(item => item.payload?.source === 'holerite');
    $('payslipList').innerHTML = payslips.map(item => {
        const fileName = item.payload?.fileName || '';
        const notes = item.payload?.notes || '';
        return `
            <article class="list-item">
                <div class="list-row"><strong>${html(periodText(item.periodType))}</strong><b>${money(item.netAmount)}</b></div>
                <span class="muted">Competencia ${html(monthLabel(item.referenceMonth))} | Recebido em ${dateBr(item.paymentDate)}</span>
                ${fileName ? `<span class="muted">Arquivo: ${html(fileName)}</span>` : ''}
                ${notes ? `<span class="muted">${html(notes)}</span>` : ''}
                <div class="list-row"><span></span><button class="tiny-btn" type="button" onclick="deleteIncome(${Number(item.id)})">Excluir</button></div>
            </article>
        `;
    }).join('') || '<p class="muted">Nenhum holerite registrado nesta competencia.</p>';
}

function clearPayslipForm() {
    $('payslipForm').reset();
    $('payslipMonth').value = state.month || currentMonth();
    $('payslipDate').value = todayIso();
    $('payslipPeriod').value = 'dia20';
    $('payslipStatus').textContent = '';
}

function updateIncomeDiscountFields() {
    const noDiscount = $('incomePeriod').value === 'dia20';
    const transportActive = $('transportActive');
    const transportManual = $('transportManual');
    const otherDiscounts = $('otherDiscounts');
    transportActive.disabled = noDiscount;
    transportManual.disabled = noDiscount;
    otherDiscounts.disabled = noDiscount;
    if (noDiscount) {
        transportActive.checked = false;
        transportManual.value = '';
        otherDiscounts.value = '';
        $('incomeDiscountNote').textContent = 'Dia 20: INSS, vale-transporte e outros descontos estao desativados.';
    } else {
        $('incomeDiscountNote').textContent = '5o dia util: descontos permitidos conforme os campos acima.';
    }
}

function renderCards(cards, summary) {
    const byId = Object.fromEntries((summary || []).map(item => [Number(item.id), item]));
    $('cardList').innerHTML = cards.map(card => {
        const totals = byId[Number(card.id)] || {};
        return `
            <article class="list-item card-list-item">
                ${renderCardVisual(card)}
                <div class="list-row"><strong>${html(card.nickname)}</strong><b>${money(totals.available ?? card.limitTotal)}</b></div>
                <span class="muted">${html(card.institution || 'Instituicao nao informada')} | ${html(card.productName || 'Modelo nao informado')} | ${html(card.brand || cardBrandFromBin4(card.bin4) || 'Bandeira nao identificada')} final ${html(card.last4 || '----')} | usado ${money(totals.used || 0)} de ${money(card.limitTotal)}</span>
                <div class="list-row">
                    <span class="muted">Fechamento dia ${Number(card.closingDay)} | vencimento dia ${Number(card.dueDay)}</span>
                    <span>
                        <button class="tiny-btn" type="button" onclick="editCard(${Number(card.id)})">Editar</button>
                        <button class="tiny-btn" type="button" onclick="deleteCard(${Number(card.id)})">Excluir</button>
                    </span>
                </div>
            </article>
        `;
    }).join('') || '<p class="muted">Nenhum cartao cadastrado.</p>';
}

function clearCardForm() {
    $('cardForm').reset();
    $('cardId').value = '';
    $('cardHolderName').value = state.user?.name || '';
    $('cardBin4').value = '';
    $('cardInstitution').value = '';
    $('cardProduct').value = '';
    $('cardExpiry').value = '';
    $('cardDetectionNote').textContent = 'Digite o numero completo para identificar a bandeira pelos 4 primeiros digitos.';
    $('cardClosing').value = 1;
    $('cardDue').value = 10;
}

function editCard(id) {
    const card = (state.dashboard.cards || []).find(row => Number(row.id) === Number(id));
    if (!card) return;
    $('cardId').value = card.id;
    $('cardNickname').value = card.nickname;
    $('cardHolderName').value = card.cardholderName || state.user?.name || '';
    $('cardInstitution').value = card.institution || '';
    $('cardProduct').value = card.productName || '';
    $('cardBrand').value = card.brand;
    $('cardBin4').value = card.bin4 || '';
    $('cardNumber').value = card.last4;
    $('cardExpiry').value = card.expiryDate || '';
    updateCardBrandDetection();
    $('cardLimit').value = String(card.limitTotal).replace('.', ',');
    $('cardClosing').value = card.closingDay;
    $('cardDue').value = card.dueDay;
    activateTab('cards');
}

async function deleteCard(id) {
    if (!confirm('Desativar este cartao?')) return;
    state.dashboard = await financePost('delete_card', { id });
    renderDashboard();
}

function renderGoals(items) {
    $('goalList').innerHTML = items.map(item => {
        const percent = item.targetAmount > 0 ? Math.min(100, Math.round((item.savedAmount / item.targetAmount) * 100)) : 0;
        return `
            <article class="list-item">
                <div class="list-row"><strong>${html(item.name)}</strong><b>${percent}%</b></div>
                <span class="muted">${money(item.savedAmount)} guardado de ${money(item.targetAmount)}</span>
                <div class="bar-track"><span class="bar-fill" style="width:${Math.max(4, percent)}%"></span></div>
                <div class="list-row">
                    <span class="muted">${html(monthLabel(item.referenceMonth))}</span>
                    <span>
                        <button class="tiny-btn" type="button" onclick="editGoal(${Number(item.id)})">Editar</button>
                        <button class="tiny-btn" type="button" onclick="deleteGoal(${Number(item.id)})">Excluir</button>
                    </span>
                </div>
            </article>
        `;
    }).join('') || '<p class="muted">Nenhuma meta registrada.</p>';
}

function clearGoalForm() {
    $('goalForm').reset();
    $('goalId').value = '';
}

function editGoal(id) {
    const goal = (state.dashboard.goals || []).find(row => Number(row.id) === Number(id));
    if (!goal) return;
    $('goalId').value = goal.id;
    $('goalName').value = goal.name;
    $('goalTarget').value = String(goal.targetAmount).replace('.', ',');
    $('goalSaved').value = String(goal.savedAmount).replace('.', ',');
    activateTab('goals');
}

async function deleteGoal(id) {
    if (!confirm('Excluir esta meta?')) return;
    state.dashboard = await financePost('delete_goal', { id });
    renderDashboard();
}

function renderAssistantBase(data) {
    $('assistantAnswer').textContent = (data.insights || []).map((item, index) => `${index + 1}. ${item}`).join('\n') || 'Sem dados suficientes.';
}

function askAssistant() {
    const data = state.dashboard || {};
    const s = data.summary || {};
    const question = $('assistantQuestion').value.trim();
    const lines = [];
    lines.push('Leitura financeira do mes:');
    lines.push(`Receita liquida total: ${money(s.income)}.`);
    lines.push(`Gastos e faturas: ${money(s.expenses)}.`);
    lines.push(`Saldo disponivel projetado: ${money(s.availableBalance)}.`);
    lines.push(`Pendencias: ${money(s.pending)}.`);
    lines.push('');
    if (question) {
        lines.push(`Sobre sua pergunta: "${question}"`);
    }
    (data.insights || []).forEach(item => lines.push(`- ${item}`));
    if ((s.availableBalance || 0) > 0) {
        const reserve = Math.max(10, Math.min(Number(s.availableBalance) * 0.2, Number(s.income || 0) * 0.1));
        lines.push(`Sugestao pratica: separe primeiro ${money(reserve)} para reserva/meta e use o restante com cuidado.`);
    }
    if ((s.pending || 0) > 0) {
        lines.push('Prioridade: pagar ou renegociar pendencias antes de assumir nova parcela.');
    }
    lines.push('');
    lines.push('Observacao: esta orientacao e educativa e nao substitui contador, banco ou planejador financeiro.');
    $('assistantAnswer').textContent = lines.join('\n');
}

function loadExternalScript(src, globalName) {
    if (window[globalName]) return Promise.resolve(window[globalName]);
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.onload = () => window[globalName] ? resolve(window[globalName]) : reject(new Error(`Biblioteca ${globalName} nao carregou.`));
        script.onerror = () => reject(new Error(`Nao foi possivel carregar a biblioteca ${globalName}.`));
        document.head.appendChild(script);
    });
}

function loadTesseract() {
    if (window.Tesseract) return Promise.resolve(window.Tesseract);
    if (state.tesseractLoading) return state.tesseractLoading;
    state.tesseractLoading = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js';
        script.onload = () => resolve(window.Tesseract);
        script.onerror = () => reject(new Error('Nao foi possivel carregar o OCR automatico. Cole o texto manualmente.'));
        document.head.appendChild(script);
    });
    return state.tesseractLoading;
}

function loadPdfJs() {
    if (window.pdfjsLib) return Promise.resolve(window.pdfjsLib);
    if (state.pdfLoading) return state.pdfLoading;
    state.pdfLoading = loadExternalScript(
        'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js',
        'pdfjsLib',
    ).then(pdfjsLib => {
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        return pdfjsLib;
    });
    return state.pdfLoading;
}

function isPdfFile(file) {
    return file.type === 'application/pdf' || /\.pdf$/i.test(file.name || '');
}

function isImageFile(file) {
    return file.type.startsWith('image/') || /\.(png|jpe?g|webp|bmp)$/i.test(file.name || '');
}

function updateOcrStatus(progress) {
    if (!progress || !progress.status) return;
    const pct = progress.progress ? ` ${Math.round(progress.progress * 100)}%` : '';
    $('ocrStatus').textContent = `${progress.status}${pct}`;
}

async function recognizeImage(source) {
    const Tesseract = await loadTesseract();
    const result = await Tesseract.recognize(source, 'por', { logger: updateOcrStatus });
    return result?.data?.text || '';
}

async function extractPdfText(file) {
    const pdfjsLib = await loadPdfJs();
    const bytes = new Uint8Array(await file.arrayBuffer());
    const pdf = await pdfjsLib.getDocument({ data: bytes }).promise;
    const pages = [];

    for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber += 1) {
        $('ocrStatus').textContent = `Lendo pagina ${pageNumber} de ${pdf.numPages}...`;
        const page = await pdf.getPage(pageNumber);
        const content = await page.getTextContent();
        const positionedItems = (content.items || [])
            .filter(item => (item.str || '').trim())
            .map(item => ({
                text: item.str.trim(),
                x: Number(item.transform?.[4] || 0),
                y: Number(item.transform?.[5] || 0),
            }))
            .sort((a, b) => b.y - a.y || a.x - b.x);
        const pdfLines = [];
        positionedItems.forEach(item => {
            const current = pdfLines[pdfLines.length - 1];
            if (!current || Math.abs(current.y - item.y) > 3) {
                pdfLines.push({ y: item.y, parts: [item.text] });
            } else {
                current.parts.push(item.text);
            }
        });
        const pageText = pdfLines.map(line => line.parts.join(' ')).join('\n').replace(/\s+\n/g, '\n').trim();
        if (pageText) {
            pages.push(pageText);
            continue;
        }

        // PDF escaneado: renderiza a imagem e usa o mesmo OCR de PNG/JPG.
        const viewport = page.getViewport({ scale: 2 });
        const canvas = document.createElement('canvas');
        canvas.width = Math.ceil(viewport.width);
        canvas.height = Math.ceil(viewport.height);
        await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
        pages.push(await recognizeImage(canvas));
    }
    return pages.filter(Boolean).join('\n');
}

async function readImportFile() {
    const file = $('importFile').files && $('importFile').files[0];
    if (!file) {
        alert('Selecione um arquivo ou cole o texto do extrato.');
        return;
    }
    $('ocrStatus').textContent = 'Lendo arquivo...';
    try {
        if (isImageFile(file)) {
            $('ocrStatus').textContent = 'OCR em andamento. Isso pode demorar um pouco...';
            $('extractedText').value = await recognizeImage(file);
        } else if (isPdfFile(file)) {
            $('extractedText').value = await extractPdfText(file);
        } else {
            $('extractedText').value = await file.text();
        }
        const size = $('extractedText').value.trim().length;
        $('ocrStatus').textContent = size ? `Leitura concluida (${size} caracteres). Agora clique em Revisar movimentos.` : 'Nenhum texto foi encontrado. Tente uma imagem mais nitida ou cole o texto manualmente.';
    } catch (error) {
        $('ocrStatus').textContent = error.message || 'Falha no OCR. Cole o texto manualmente.';
    }
}

async function reviewImport() {
    if (!$('extractedText').value.trim()) {
        $('ocrStatus').textContent = 'Leia um arquivo ou cole o texto do extrato antes de revisar.';
        return;
    }
    try {
        $('ocrStatus').textContent = 'Analisando movimentos e procurando cartoes cadastrados...';
        const data = await financePost('import_statement', {
            extractedText: $('extractedText').value,
            fileName: $('importFile').files?.[0]?.name || '',
            importType: 'extrato',
            periodType: $('importPeriod').value,
            confirm: false,
        });
        state.pendingImport = data.movements || [];
        renderImportPreview();
        $('ocrStatus').textContent = data.message || 'Movimentos reconhecidos.';
    } catch (error) {
        $('ocrStatus').textContent = error.message || 'Falha ao analisar o extrato.';
    }
}

function options(current, values) {
    return values.map(([value, label]) => `<option value="${value}" ${value === current ? 'selected' : ''}>${label}</option>`).join('');
}

function cardOptions(current) {
    const cards = state.dashboard?.cards || [];
    return '<option value="">Sem cartao vinculado</option>' + cards.map(card =>
        `<option value="${Number(card.id)}" ${Number(card.id) === Number(current || 0) ? 'selected' : ''}>${html(card.nickname)} | final ${html(card.last4 || '----')}</option>`
    ).join('');
}

function importItemIsCard(item) {
    const bankMethod = ['pix', 'debito'].includes(item.paymentMethod);
    return Boolean(item.cardId || item.cardLast4)
        || (!bankMethod && (item.paymentMethod === 'credito' || ['cartao_credito', 'fatura_cartao'].includes(item.type)));
}

function importRowIsCard(row) {
    const type = row.querySelector('[data-field="type"]')?.value || '';
    const method = row.querySelector('[data-field="paymentMethod"]')?.value || '';
    const cardId = row.querySelector('[data-field="cardId"]')?.value || '';
    const last4 = (row.querySelector('[data-field="cardLast4"]')?.value || '').replace(/\D/g, '');
    const bankMethod = ['pix', 'debito'].includes(method);
    return Boolean(cardId || last4) || (!bankMethod && (method === 'credito' || ['cartao_credito', 'fatura_cartao'].includes(type)));
}

function refreshImportRowContext(row, resetPeriod = false) {
    const type = row.querySelector('[data-field="type"]')?.value || '';
    const method = row.querySelector('[data-field="paymentMethod"]')?.value || '';
    const period = row.querySelector('[data-field="periodType"]');
    const card = importRowIsCard(row);
    const needsBalanceChoice = !card && ['pix', 'debito'].includes(method);

    if (period) {
        period.required = needsBalanceChoice;
        if (needsBalanceChoice && resetPeriod) period.value = '';
        if (!needsBalanceChoice && !period.value) period.value = 'dia20';
    }

    const help = row.querySelector('[data-import-help]');
    if (!help) return;
    if (card) {
        const date = row.querySelector('[data-field="movementDate"]')?.value || '';
        if (creditPurchaseAfterCutoff(date)) {
            const cutoff = fifthBusinessDayIso(date);
            help.textContent = `Compra no cartao apos o 5o dia util (${dateBr(cutoff)}): vinculada pelo final 4 e programada para ${dateBr(shiftIsoMonth(date, 1))}.`;
        } else {
            help.textContent = 'Compra no cartao ate o 5o dia util: confira o final 4 e o cartao cadastrado. Ela sera lancada no mes atual.';
        }
    } else if (needsBalanceChoice) {
        help.textContent = 'Pix/debito: selecione a data do pagamento e o saldo de origem usado (Dia 20 ou 5o dia util).';
    } else {
        help.textContent = `Metodo ${method || 'nao identificado'}: confira a data e o saldo de origem antes de salvar.`;
    }
}

function renderImportPreview() {
    $('importPreview').innerHTML = state.pendingImport.map((item, index) => {
        const card = importItemIsCard(item);
        const needsBalanceChoice = !card && ['pix', 'debito'].includes(item.paymentMethod);
        const periodValue = needsBalanceChoice && !item.periodTypeConfirmed ? '' : (item.periodType || 'dia20');
        const periodOptions = needsBalanceChoice
            ? [['', 'Selecione o saldo usado'], ['dia20', 'Saldo do Dia 20'], ['quinto_util', 'Saldo do 5o dia util']]
            : [['dia20', 'Dia 20'], ['quinto_util', '5o dia util']];
        const helpClass = item.category === 'Pensao' ? 'is-pension' : item.cardMatch === 'matched' ? 'is-ok' : item.cardLast4 ? 'is-alert' : '';
        const helpText = item.category === 'Pensao'
            ? 'Pensao identificada e classificada como saida.'
            : card
                ? 'Compra no cartao: confira o final 4 e o cartao cadastrado. Ela sera lancada em contas feitas no cartao.'
                : needsBalanceChoice
                    ? 'Pix/debito: selecione a data do pagamento e o saldo de origem usado (Dia 20 ou 5o dia util).'
                    : 'Metodo identificado automaticamente; confira os campos antes de salvar.';
        return `
            <div class="movement-row" data-index="${index}" data-statement-installment="${Number(item.installmentCount || 1) > 1 ? 'true' : 'false'}">
                <div class="field"><label>Nome / estabelecimento</label><input class="plain-input" data-field="description" value="${html(item.description)}"></div>
                <div class="field"><label>Valor em reais</label><input class="plain-input" data-field="amount" value="${String(item.amount || '').replace('.', ',')}"></div>
                ${item.itemDetails ? `<div class="field item-details-field"><label>Produtos identificados</label><textarea class="plain-textarea" data-field="itemDetails">${html(item.itemDetails)}</textarea></div>` : ''}
                <div class="field"><label>Parcela atual</label><input class="plain-input" data-field="installmentNumber" type="number" min="1" max="60" value="${Number(item.installmentNumber || 1)}"></div>
                <div class="field"><label>Total de parcelas</label><input class="plain-input" data-field="installmentCount" type="number" min="1" max="60" value="${Number(item.installmentCount || 1)}"></div>
                <div class="field"><label>Valor desta parcela</label><input class="plain-input" data-field="installmentAmount" readonly value="${money(item.installmentAmount || item.amount || 0)}"></div>
                <div class="field"><label>Data do pagamento</label><input class="plain-input" type="date" data-field="movementDate" value="${html(item.movementDate || todayIso())}"></div>
                <div class="field"><label>Hora</label><input class="plain-input" type="time" data-field="movementTime" value="${html(item.movementTime || '')}"></div>
                <div class="field"><label>${needsBalanceChoice ? 'Saldo de origem *' : 'Saldo de origem'}</label><select class="plain-select" data-field="periodType" ${needsBalanceChoice ? 'required' : ''}>${options(periodValue, periodOptions)}</select></div>
                <div class="field"><label>Tipo identificado</label><select class="plain-select" data-field="type">${options(item.type || 'saida', [['saida', 'Saida'], ['entrada', 'Entrada'], ['cartao_credito', 'Compra no cartao'], ['fatura_cartao', 'Fatura de cartao']])}</select></div>
                <div class="field"><label>Categoria</label><input class="plain-input" data-field="category" value="${html(item.category || '')}"></div>
                <div class="field"><label>Metodo identificado</label><select class="plain-select" data-field="paymentMethod">${options(item.paymentMethod || 'outro', [['pix', 'Pix'], ['dinheiro', 'Dinheiro'], ['debito', 'Debito'], ['credito', 'Credito/cartao'], ['boleto', 'Boleto'], ['transferencia', 'Transferencia'], ['outro', 'Outro']])}</select></div>
                <div class="field"><label>4 ultimos do cartao</label><input class="plain-input" data-field="cardLast4" inputmode="numeric" maxlength="4" value="${html(item.cardLast4 || '')}"></div>
                <div class="field"><label>Cartao cadastrado</label><select class="plain-select" data-field="cardId">${cardOptions(item.cardId)}</select></div>
                <div class="field"><label>Status</label><select class="plain-select" data-field="status">${options(item.status || 'pago', [['pago', 'Pago'], ['pendente', 'Pendente'], ['programado', 'Programado']])}</select></div>
                <div class="import-match ${helpClass}" data-import-help>${helpText}</div>
            </div>
        `;
    }).join('') || '<p class="muted">Nenhum movimento reconhecido ainda.</p>';

    document.querySelectorAll('.movement-row [data-field="cardId"]').forEach(select => {
        select.addEventListener('change', () => {
            const row = select.closest('.movement-row');
            if (select.value) {
                row.querySelector('[data-field="type"]').value = 'cartao_credito';
                row.querySelector('[data-field="paymentMethod"]').value = 'credito';
            }
            refreshImportRowContext(row);
        });
    });
    document.querySelectorAll('.movement-row').forEach(row => {
        const amount = row.querySelector('[data-field="amount"]');
        const count = row.querySelector('[data-field="installmentCount"]');
        const number = row.querySelector('[data-field="installmentNumber"]');
        const monthly = row.querySelector('[data-field="installmentAmount"]');
        const update = () => {
            const total = parseMoney(amount.value);
            const totalCount = Math.max(1, Math.min(60, Number(count.value || 1)));
            count.value = totalCount;
            number.value = Math.max(1, Math.min(totalCount, Number(number.value || 1)));
            monthly.value = money(row.dataset.statementInstallment === 'true' ? total : total / totalCount);
        };
        amount.addEventListener('input', update);
        count.addEventListener('input', update);
        number.addEventListener('change', update);
        row.querySelector('[data-field="paymentMethod"]').addEventListener('change', () => refreshImportRowContext(row, true));
        row.querySelector('[data-field="type"]').addEventListener('change', () => refreshImportRowContext(row, true));
        row.querySelector('[data-field="cardLast4"]').addEventListener('input', () => refreshImportRowContext(row));
        row.querySelector('[data-field="movementDate"]').addEventListener('input', () => refreshImportRowContext(row));
        update();
        refreshImportRowContext(row);
    });
}

function collectImportMovements() {
    return Array.from(document.querySelectorAll('.movement-row')).map(row => {
        const item = {
            referenceMonth: state.month,
            source: 'extrato',
            notes: state.pendingImport[Number(row.dataset.index)]?.notes || 'Lancado por importacao revisada.',
        };
        row.querySelectorAll('[data-field]').forEach(input => {
            const field = input.dataset.field;
            if (field === 'amount') item[field] = parseMoney(input.value);
            else if (field === 'cardId') item[field] = Number(input.value || 0) || null;
            else if (field === 'cardLast4') item[field] = input.value.replace(/\D/g, '').slice(-4);
            else if (field === 'installmentAmount') item[field] = parseMoney(input.value);
            else if (field === 'installmentCount' || field === 'installmentNumber') item[field] = Math.max(1, Number(input.value || 1));
            else item[field] = input.value;
        });
        if (!/^\d{4}-\d{2}-\d{2}$/.test(item.movementDate || '')) {
            throw new Error('Informe uma data valida para cada movimento importado.');
        }
        item.referenceMonth = item.movementDate.slice(0, 7);
        const isCard = importRowIsCard(row);
        if (!isCard && ['pix', 'debito'].includes(item.paymentMethod)) {
            if (!item.periodType) {
                throw new Error('Selecione o saldo de origem (Dia 20 ou 5o dia util) para cada Pix ou debito.');
            }
            item.periodTypeConfirmed = true;
        } else {
            item.periodTypeConfirmed = true;
        }
        return item;
    }).filter(item => item.description && Number(item.amount) > 0);
}

async function confirmImport() {
    let movements;
    try {
        movements = collectImportMovements();
    } catch (error) {
        alert(error.message || 'Revise a data e o saldo de origem dos movimentos.');
        return;
    }
    if (!movements.length) {
        alert('Nenhum movimento valido para salvar.');
        return;
    }
    try {
        $('ocrStatus').textContent = 'Salvando lancamentos inteligentes...';
        const importedDashboard = await financePost('import_statement', {
            extractedText: $('extractedText').value,
            fileName: $('importFile').files?.[0]?.name || '',
            importType: 'extrato',
            periodType: $('importPeriod').value,
            movements,
            confirm: true,
        });
        state.dashboard = importedDashboard;
        state.pendingImport = [];
        renderImportPreview();
        renderDashboard();
        const futureMonths = (importedDashboard.importedMonths || [])
            .filter(month => month && month !== state.month)
            .sort();
        $('ocrStatus').textContent = futureMonths.length
            ? `Lancamentos importados. Os movimentos de ${futureMonths.map(monthLabel).join(', ')} aparecerao ao selecionar esse mes no topo.`
            : 'Lancamentos importados com sucesso.';
    } catch (error) {
        $('ocrStatus').textContent = error.message || 'Falha ao salvar os lancamentos importados.';
    }
}

async function bankStatus() {
    const data = await api('api/finance?action=bank_status');
    $('ocrStatus').textContent = data.message || 'API bancaria preparada.';
}

function activateTab(name) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.toggle('is-active', btn.dataset.tab === name));
    document.querySelectorAll('.panel').forEach(panel => panel.classList.toggle('is-active', panel.id === `panel-${name}`));
}

document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => activateTab(btn.dataset.tab));
});

$('monthFilter').addEventListener('change', loadDashboard);
$('cardNumber').addEventListener('input', updateCardBrandDetection);
$('cardBin4').addEventListener('input', updateCardBrandDetection);
$('transactionType').addEventListener('change', () => { updateInstallmentFields(); updateCreditScheduleNote(); });
$('transactionMethod').addEventListener('change', () => { updateInstallmentFields(); updateCreditScheduleNote(); });
$('transactionCard').addEventListener('change', () => { updateInstallmentFields(); updateCreditScheduleNote(); });
$('transactionDate').addEventListener('input', updateCreditScheduleNote);
$('transactionAmount').addEventListener('input', calculateInstallmentAmount);
$('transactionInstallments').addEventListener('input', calculateInstallmentAmount);
$('incomePeriod').addEventListener('change', updateIncomeDiscountFields);

$('transactionForm').addEventListener('submit', async event => {
    event.preventDefault();
    const savedDashboard = await financePost('save_transaction', {
        transaction: {
            id: Number($('transactionId').value || 0),
            type: $('transactionType').value,
            periodType: $('transactionPeriod').value,
            movementDate: $('transactionDate').value || todayIso(),
            movementTime: $('transactionTime').value || '',
            amount: parseMoney($('transactionAmount').value),
            installmentCount: Number($('transactionInstallments').value || 1),
            installmentNumber: 1,
            description: $('transactionDescription').value,
            category: $('transactionCategory').value,
            paymentMethod: $('transactionMethod').value,
            status: $('transactionStatus').value,
            cardId: Number($('transactionCard').value || 0),
            referenceMonth: state.month,
            notes: $('transactionNotes').value,
        },
    });
    state.dashboard = savedDashboard;
    clearTransactionForm();
    renderDashboard();
    const scheduledMonths = (savedDashboard.scheduledMonths || []).filter(month => month && month !== state.month);
    if (scheduledMonths.length) {
        $('transactionCreditRuleNote').textContent = `Compra no credito programada para ${scheduledMonths.map(monthLabel).join(', ')}. Selecione esse mes no topo para visualizar o lancamento.`;
    }
});

$('incomeForm').addEventListener('submit', async event => {
    event.preventDefault();
    state.dashboard = await financePost('save_income', {
        income: {
            referenceMonth: state.month,
            periodType: $('incomePeriod').value,
            paymentDate: $('incomeDate').value || todayIso(),
            grossSalary: parseMoney($('grossSalary').value),
            benefits: parseMoney($('benefits').value),
            nightShift: parseMoney($('nightShift').value),
            hazardPay: parseMoney($('hazardPay').value),
            extraIncome: parseMoney($('extraIncome').value),
            otherDiscounts: parseMoney($('otherDiscounts').value),
            transportActive: $('transportActive').checked,
            transportManual: parseMoney($('transportManual').value),
            notes: $('incomeNotes').value,
        },
    });
    renderDashboard();
});

$('payslipForm').addEventListener('submit', async event => {
    event.preventDefault();
    const netAmount = parseMoney($('payslipNet').value);
    if (netAmount <= 0) {
        $('payslipStatus').textContent = 'Informe um ganho liquido maior que zero.';
        $('payslipNet').focus();
        return;
    }
    try {
        $('payslipStatus').textContent = 'Salvando holerite...';
        const file = $('payslipFile').files?.[0];
        const referenceMonth = $('payslipMonth').value || state.month;
        state.dashboard = await financePost('save_payslip', {
            payslip: {
                referenceMonth,
                periodType: $('payslipPeriod').value,
                paymentDate: $('payslipDate').value || todayIso(),
                netAmount,
                fileName: file?.name || '',
                notes: $('payslipNotes').value,
            },
        });
        state.month = referenceMonth;
        $('monthFilter').value = referenceMonth;
        clearPayslipForm();
        $('payslipStatus').textContent = 'Holerite salvo. O ganho liquido ja foi incluido no dashboard.';
        renderDashboard();
    } catch (error) {
        $('payslipStatus').textContent = error.message || 'Nao foi possivel salvar o holerite.';
    }
});

$('cardForm').addEventListener('submit', async event => {
    event.preventDefault();
    state.dashboard = await financePost('save_card', {
        card: {
            id: Number($('cardId').value || 0),
            nickname: $('cardNickname').value,
            cardholderName: $('cardHolderName').value,
            institution: $('cardInstitution').value,
            productName: $('cardProduct').value,
            brand: $('cardBrand').value,
            bin4: $('cardBin4').value,
            number: $('cardNumber').value,
            expiryDate: $('cardExpiry').value,
            limitTotal: parseMoney($('cardLimit').value),
            closingDay: Number($('cardClosing').value || 1),
            dueDay: Number($('cardDue').value || 10),
            active: true,
        },
    });
    clearCardForm();
    renderDashboard();
});

$('goalForm').addEventListener('submit', async event => {
    event.preventDefault();
    state.dashboard = await financePost('save_goal', {
        goal: {
            id: Number($('goalId').value || 0),
            name: $('goalName').value,
            targetAmount: parseMoney($('goalTarget').value),
            savedAmount: parseMoney($('goalSaved').value),
            referenceMonth: state.month,
            active: true,
        },
    });
    clearGoalForm();
    renderDashboard();
});

(async function init() {
    try {
        const ok = await requireAuth();
        if (!ok) return;
        $('monthFilter').value = currentMonth();
        $('cardHolderName').value = state.user?.name || '';
        $('transactionDate').value = todayIso();
        $('incomeDate').value = todayIso();
        $('payslipMonth').value = currentMonth();
        $('payslipDate').value = todayIso();
        updateInstallmentFields();
        updateCreditScheduleNote();
        updateIncomeDiscountFields();
        await loadDashboard();
    } catch (error) {
        console.error(error);
        window.location.href = 'login';
    }
})();
