const state = {
    user: null,
    month: '',
    dashboard: null,
    pendingImport: [],
    tesseractLoading: null,
    pdfLoading: null,
    businessModule: null,
};
window.financeState = state;

const PROFILE_CONFIG = {
    clt: {
        label: 'CLT',
        description: 'Salario, holerite, descontos e recebimentos por periodo.',
        modules: ['Holerite', 'Recebimentos', 'Cartoes', 'Contas e metas'],
        incomeTitle: 'Registrar pagamento CLT',
        incomeDescription: 'Recebimentos do Dia 20 entram sem descontos. No 5º dia útil, o sistema calcula INSS progressivo e vale-transporte de até 6%.',
        showPayslip: true,
        showProfessionalDashboard: false,
        usesSalaryPeriods: true,
        tabs: { movements: 'Movimentos', income: 'Recebimentos', payslips: 'Holerite', cards: 'Cartoes', imports: 'Importar extrato', goals: 'Metas e IA' },
        transactionTitle: 'Novo lancamento',
        transactionDescription: 'Registre entrada, saida, fatura ou compra no cartao.',
        transactionListTitle: 'Movimentos do mes',
        incomeListTitle: 'Recebimentos registrados',
        incomeAmountLabel: 'Salario bruto',
        incomeSubmitLabel: 'Calcular e salvar',
        showSalaryComponents: true,
    },
    pj: {
        label: 'Prestador PJ',
        description: 'Faturamento, recebimentos de clientes, impostos e pro-labore.',
        modules: ['Receitas PJ', 'Despesas da empresa', 'Veiculos e imoveis', 'Impostos e impostometro', 'Recursos premium'],
        incomeTitle: 'Registrar recebimento PJ',
        incomeDescription: 'Use os recebimentos para registrar faturamento, pro-labore e valores recebidos de clientes.',
        showPayslip: false,
        showProfessionalDashboard: true,
        dashboardTitle: 'Financeiro PJ',
        dashboardDescription: 'Acompanhe o faturamento recebido, as despesas da atividade e o saldo operacional da competencia.',
        metricLabels: ['Receita recebida', 'Despesas PJ', 'Saldo operacional'],
        usesSalaryPeriods: false,
        tabs: { movements: 'Despesas PJ', income: 'Receitas PJ', payslips: 'Holerite', cards: 'Cartoes', imports: 'Importar extratos', goals: 'Metas e IA', professional: 'Profissional' },
        transactionTitle: 'Nova despesa PJ',
        transactionDescription: 'Registre despesas da empresa, fornecedores, faturas ou compras no cartao.',
        transactionListTitle: 'Despesas da competencia',
        incomeListTitle: 'Recebimentos de clientes',
        incomeAmountLabel: 'Faturamento / valor recebido',
        incomeSubmitLabel: 'Salvar recebimento PJ',
        showSalaryComponents: false,
    },
    comerciante_pf: {
        label: 'Comerciante PF',
        description: 'Vendas, entradas, compras e controle do caixa pessoal.',
        modules: ['Vendas e recebimentos', 'Compras', 'Veiculos e imoveis', 'Impostos e impostometro', 'Recursos premium'],
        incomeTitle: 'Registrar vendas e recebimentos',
        incomeDescription: 'Registre as entradas das vendas e acompanhe compras, gastos e o caixa pessoal.',
        showPayslip: false,
        showProfessionalDashboard: true,
        dashboardTitle: 'Financeiro Comerciante PF',
        dashboardDescription: 'Visualize as vendas recebidas, as compras e o caixa disponivel da sua atividade comercial.',
        metricLabels: ['Vendas recebidas', 'Compras e gastos', 'Caixa disponivel'],
        usesSalaryPeriods: false,
        tabs: { movements: 'Compras e gastos', income: 'Vendas e recebimentos', payslips: 'Holerite', cards: 'Cartoes', imports: 'Extratos e vendas', goals: 'Caixa e metas', professional: 'Profissional' },
        transactionTitle: 'Nova compra ou gasto',
        transactionDescription: 'Registre compras, despesas, pagamentos a fornecedores ou compras no cartao.',
        transactionListTitle: 'Compras e gastos da competencia',
        incomeListTitle: 'Vendas e recebimentos',
        incomeAmountLabel: 'Valor da venda / recebimento',
        incomeSubmitLabel: 'Salvar venda',
        showSalaryComponents: false,
    },
    comerciante_pj: {
        label: 'Comerciante PJ',
        description: 'Vendas da empresa, despesas operacionais, fornecedores e caixa.',
        modules: ['Vendas da empresa', 'Despesas operacionais', 'Veiculos e imoveis', 'Impostos e impostometro', 'Recursos premium'],
        incomeTitle: 'Registrar vendas da empresa',
        incomeDescription: 'Organize as entradas das vendas, despesas operacionais e o caixa da empresa.',
        showPayslip: false,
        showProfessionalDashboard: true,
        dashboardTitle: 'Financeiro Comerciante PJ',
        dashboardDescription: 'Acompanhe as vendas da empresa, as despesas operacionais e o saldo do caixa empresarial.',
        metricLabels: ['Vendas recebidas', 'Despesas operacionais', 'Caixa da empresa'],
        usesSalaryPeriods: false,
        tabs: { movements: 'Despesas operacionais', income: 'Vendas da empresa', payslips: 'Holerite', cards: 'Cartoes', imports: 'Extratos da empresa', goals: 'Caixa e metas', professional: 'Profissional' },
        transactionTitle: 'Nova despesa operacional',
        transactionDescription: 'Registre fornecedores, custos operacionais, faturas ou compras no cartao.',
        transactionListTitle: 'Despesas operacionais da competencia',
        incomeListTitle: 'Vendas da empresa',
        incomeAmountLabel: 'Valor da venda / recebimento',
        incomeSubmitLabel: 'Salvar venda da empresa',
        showSalaryComponents: false,
    },
};

const BUSINESS_MODULES = {
    schedule: {
        eyebrow: 'Operação', icon: 'agenda', title: 'Agendamento',
        description: 'Organize compromissos, vencimentos e atividades importantes da sua operação em uma visão única.',
        status: 'Agenda centralizada para reduzir esquecimentos.',
        cards: [
            { title: 'Agenda financeira', text: 'Acompanhe o calendário da competência, recebimentos e próximos vencimentos.', action: 'Abrir financeiro', target: 'dashboard' },
            { title: 'Próximos compromissos', text: 'Use o período selecionado para visualizar o que precisa ser resolvido primeiro.', action: 'Ver visão mensal', target: 'dashboard' },
        ],
    },
    cash: {
        eyebrow: 'Operação', icon: 'wallet', title: 'Caixa',
        description: 'Tenha uma leitura rápida do caixa disponível, das entradas previstas e das saídas da atividade.',
        status: 'O caixa é atualizado com seus lançamentos.',
        cards: [
            { title: 'Saldo disponível', text: 'Veja o saldo consolidado e os indicadores de saúde financeira.', action: 'Ver saldo', target: 'dashboard' },
            { title: 'Entrada rápida', text: 'Registre vendas, recebimentos de clientes ou outras entradas.', action: 'Registrar entrada', target: 'income' },
            { title: 'Saída rápida', text: 'Registre uma compra, custo operacional ou pagamento.', action: 'Registrar saída', target: 'movements' },
        ],
    },
    clients: {
        eyebrow: 'Cadastros', icon: 'users', title: 'Clientes',
        description: 'Centralize os dados de clientes e mantenha a operação preparada para acompanhar recebimentos e serviços.',
        status: 'Área de relacionamento preparada para o seu cadastro.',
        cards: [
            { title: 'Cadastro de clientes', text: 'Mantenha nome, documento, contato e observações importantes em um só lugar.', action: 'Abrir cadastros', target: 'business:registrations' },
            { title: 'Recebimentos por cliente', text: 'Consulte as entradas registradas e organize a origem das vendas.', action: 'Ver recebimentos', target: 'income' },
        ],
    },
    professional: {
        eyebrow: 'Gestão profissional', icon: 'briefcase', title: 'Profissional',
        description: 'Acesse patrimônio, veículos, imóveis, impostos, impostômetro e indicadores avançados.',
        status: 'Recursos profissionais ativos para este perfil.',
        cards: [
            { title: 'Patrimônio e bens', text: 'Controle veículos, imóveis, custos recorrentes e valores estimados.', action: 'Abrir patrimônio', target: 'professional' },
            { title: 'Impostos e impostômetro', text: 'Registre tributos, vencimentos e acompanhe o que já foi pago.', action: 'Abrir impostos', target: 'professional' },
        ],
    },
    products: {
        eyebrow: 'Cadastros', icon: 'box', title: 'Produtos e serviços',
        description: 'Organize o catálogo da atividade, os itens vendidos e os serviços prestados aos seus clientes.',
        status: 'Catálogo preparado para sua operação comercial.',
        cards: [
            { title: 'Produtos', text: 'Estruture itens, categorias e informações que facilitam o acompanhamento das vendas.', action: 'Abrir cadastros', target: 'business:registrations' },
            { title: 'Serviços', text: 'Mantenha uma visão dos serviços prestados e conecte-os aos recebimentos.', action: 'Ver recebimentos', target: 'income' },
        ],
    },
    transactions: {
        eyebrow: 'Financeiro', icon: 'movements', title: 'Lançamentos',
        description: 'Registre e acompanhe entradas, saídas, compras, cartões e despesas da competência.',
        status: 'Todos os lançamentos continuam sincronizados com o Financeiro.',
        cards: [
            { title: 'Novo lançamento', text: 'Cadastre uma movimentação com categoria, forma de pagamento e parcelas.', action: 'Adicionar lançamento', target: 'movements' },
            { title: 'Movimentos recentes', text: 'Confira o histórico do mês e edite ou exclua registros quando necessário.', action: 'Consultar movimentos', target: 'movements' },
        ],
    },
    wallets: {
        eyebrow: 'Financeiro', icon: 'wallet', title: 'Meus caixas',
        description: 'Separe caixa da empresa, conta corrente e demais saldos usados na operação.',
        status: 'Visão consolidada dos saldos da atividade.',
        cards: [
            { title: 'Caixa principal', text: 'Acompanhe a disponibilidade atual e o impacto das próximas movimentações.', action: 'Ver disponibilidade', target: 'dashboard' },
            { title: 'Contas e cartões', text: 'Gerencie cartões cadastrados e identifique gastos automaticamente.', action: 'Ver contas', target: 'business:accounts' },
        ],
    },
    categories: {
        eyebrow: 'Financeiro', icon: 'cat-other', title: 'Categorias',
        description: 'Classifique produtos, serviços e despesas para gerar análises mais claras do seu resultado.',
        status: 'Categorias ilustradas para facilitar a leitura dos gastos.',
        cards: [
            { title: 'Categorias de gastos', text: 'Use categorias para separar compras, fornecedores, operação e despesas pessoais.', action: 'Abrir lançamentos', target: 'movements' },
            { title: 'Análise por categoria', text: 'Visualize a distribuição dos gastos e identifique onde otimizar.', action: 'Ver análise', target: 'dashboard' },
        ],
    },
    accounts: {
        eyebrow: 'Financeiro', icon: 'card', title: 'Contas',
        description: 'Gerencie contas bancárias, cartões e limites usados pela sua atividade.',
        status: 'Contas e cartões prontos para reconhecimento automático.',
        cards: [
            { title: 'Cartões cadastrados', text: 'Controle instituição, bandeira, limite e final do cartão.', action: 'Abrir cartões', target: 'cards' },
            { title: 'Reconhecimento inteligente', text: 'Importe comprovantes e relacione os lançamentos ao cartão correto.', action: 'Importar extrato', target: 'imports' },
        ],
    },
    'payment-methods': {
        eyebrow: 'Financeiro', icon: 'debit', title: 'Formas de pagamento',
        description: 'Padronize Pix, débito, crédito, dinheiro e outras formas usadas no seu controle.',
        status: 'Formas de pagamento organizadas no lançamento.',
        cards: [
            { title: 'Pix e débito', text: 'Registre a data correta do pagamento e o caixa de origem.', action: 'Novo lançamento', target: 'movements' },
            { title: 'Crédito e parcelas', text: 'Informe parcelas e acompanhe o valor que será lançado na fatura.', action: 'Ver cartões', target: 'cards' },
        ],
    },
    purchases: {
        eyebrow: 'Análise', icon: 'cat-market', title: 'Compras',
        description: 'Acompanhe compras de estoque, materiais, fornecedores e demais despesas da atividade.',
        status: 'Compras conectadas aos movimentos e às categorias.',
        cards: [
            { title: 'Compras da competência', text: 'Veja o que foi comprado e como cada gasto impactou o caixa.', action: 'Ver movimentos', target: 'movements' },
            { title: 'Compras no cartão', text: 'Acompanhe parcelas e o comprometimento do limite dos cartões.', action: 'Ver cartões', target: 'cards' },
        ],
    },
    registrations: {
        eyebrow: 'Cadastros', icon: 'document', title: 'Cadastros gerais',
        description: 'Reúna clientes, produtos, serviços, fornecedores e informações de apoio em uma área organizada.',
        status: 'Central de cadastros disponível para ampliar a operação.',
        cards: [
            { title: 'Base operacional', text: 'Use esta central para estruturar os dados que alimentam seus controles.', action: 'Ver produtos e serviços', target: 'business:products' },
            { title: 'Clientes e contatos', text: 'Mantenha informações importantes para suas vendas e recebimentos.', action: 'Ver clientes', target: 'business:clients' },
        ],
    },
    consultation: {
        eyebrow: 'Análise', icon: 'search', title: 'Consulta',
        description: 'Encontre rapidamente registros, lançamentos, recebimentos, cartões e obrigações.',
        status: 'Pesquisa conectada às áreas do seu Financeiro.',
        cards: [
            { title: 'Resumo da competência', text: 'Consulte receitas, gastos, saldo, parcelas e pendências do período.', action: 'Abrir resumo', target: 'dashboard' },
            { title: 'Histórico detalhado', text: 'Confira os movimentos completos e seus respectivos status.', action: 'Consultar lançamentos', target: 'movements' },
        ],
    },
    permissions: {
        eyebrow: 'Administração', icon: 'key', title: 'Permissões',
        description: 'Prepare os acessos da equipe e defina quais áreas cada usuário poderá consultar ou editar.',
        status: 'Controle de acesso pronto para configuração da equipe.',
        cards: [
            { title: 'Perfis de acesso', text: 'Organize permissões para administração, financeiro e operação.', action: 'Abrir configurações', target: 'business:settings' },
            { title: 'Segurança', text: 'Mantenha o controle dos dados da empresa e revise acessos periodicamente.', action: 'Ver perfil', target: 'profile' },
        ],
    },
    settings: {
        eyebrow: 'Administração', icon: 'settings', title: 'Configurações',
        description: 'Ajuste o perfil da atividade, preferências do Financeiro e os dados usados pelos relatórios.',
        status: 'Configurações da atividade centralizadas.',
        cards: [
            { title: 'Perfil da atividade', text: 'Revise a classificação do negócio e os recursos profissionais ativos.', action: 'Abrir perfil', target: 'profile' },
            { title: 'Preferências financeiras', text: 'Confira cartões, categorias, períodos e informações de apoio.', action: 'Ver Financeiro', target: 'dashboard' },
        ],
    },
    'tax-documents': {
        eyebrow: 'Fiscal', icon: 'document', title: 'NFSe e NFC-e',
        description: 'Centralize documentos fiscais e mantenha os registros da atividade preparados para conferência.',
        status: 'Área fiscal preparada para seus documentos.',
        cards: [
            { title: 'Documentos fiscais', text: 'Organize referências, valores e observações dos documentos emitidos ou recebidos.', action: 'Abrir profissional', target: 'professional' },
            { title: 'Impostos relacionados', text: 'Acompanhe tributos e vencimentos junto ao impostômetro.', action: 'Ver impostos', target: 'professional' },
        ],
    },
    help: {
        eyebrow: 'Suporte', icon: 'help', title: 'Ajuda',
        description: 'Encontre orientações rápidas para lançar, importar, consultar e organizar suas finanças.',
        status: 'Suporte contextual disponível nesta área.',
        cards: [
            { title: 'Importação inteligente', text: 'Use comprovantes em PNG, JPG ou PDF para identificar valores, estabelecimento, data e cartão.', action: 'Abrir importação', target: 'imports' },
            { title: 'Como começar', text: 'Cadastre cartões, registre entradas e mantenha os lançamentos atualizados.', action: 'Ver Financeiro', target: 'dashboard' },
        ],
    },
    administrative: {
        eyebrow: 'Administração', icon: 'briefcase', title: 'Administrativo',
        description: 'Acesse a visão de gestão da empresa, com patrimônio, impostos e indicadores operacionais.',
        status: 'Painel administrativo conectado ao perfil profissional.',
        cards: [
            { title: 'Indicadores da operação', text: 'Acompanhe receita, despesas, resultado, patrimônio e obrigações.', action: 'Abrir profissional', target: 'professional' },
            { title: 'Resumo para decisão', text: 'Emita o resumo financeiro em PDF com os dados da competência.', action: 'Ver Financeiro', target: 'dashboard' },
        ],
    },
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
    applyProfileConfiguration();
    showProfileOnboardingIfNeeded();
    showForcedPasswordChangeIfNeeded();
    return true;
}

function profileConfig(type = state.user?.profileType) {
    return PROFILE_CONFIG[type] || PROFILE_CONFIG.clt;
}

function applyProfileConfiguration() {
    const config = profileConfig();
    const user = state.user || {};
    $('welcomeText').textContent = `${user.name || 'Usuario'}, ${config.description.toLowerCase()}`;
    $('profileBadge').textContent = config.label;
    $('profileContextTitle').textContent = `Perfil ${config.label}`;
    $('profileContextDescription').textContent = config.description;
    $('incomePanelTitle').textContent = config.incomeTitle;
    $('incomePanelDescription').textContent = config.incomeDescription;
    $('profileName').value = user.name || '';
    $('profileEmail').value = user.email || '';
    $('profileType').value = config.label;
    $('profileDescription').textContent = config.description;
    $('profileModules').innerHTML = config.modules.map(module => `<span class="profile-module">${html(module)}</span>`).join('');
    $('professionalNav').hidden = !config.showProfessionalDashboard;
    $('panel-business-module').hidden = !config.showProfessionalDashboard;
    $('professionalNavProfile').textContent = `${config.label} | recursos profissionais`;
    Object.entries(config.tabs || {}).forEach(([tab, label]) => {
        const button = $(`${tab}Tab`);
        if (button) {
            const tabLabel = button.querySelector('.tab-label');
            if (tabLabel) tabLabel.textContent = label;
            else button.textContent = label;
        }
    });
    $('transactionPanelTitle').textContent = config.transactionTitle;
    $('transactionPanelDescription').textContent = config.transactionDescription;
    $('transactionListTitle').textContent = config.transactionListTitle;
    $('incomeListTitle').textContent = config.incomeListTitle;
    $('grossSalaryLabel').textContent = config.incomeAmountLabel;
    $('incomeSubmitButton').textContent = config.incomeSubmitLabel;
    ['benefitsField', 'nightShiftField', 'hazardPayField', 'extraIncomeField', 'otherDiscountsField', 'transportActiveField', 'transportManualField'].forEach(id => {
        $(id).hidden = !config.showSalaryComponents;
    });
    $('availableBalanceLabel').textContent = config.usesSalaryPeriods ? 'Saldo disponivel do periodo' : 'Saldo disponivel';
    $('agendaNote').textContent = config.usesSalaryPeriods
        ? 'O sistema separa as contas por competencia e por vencimento do periodo escolhido.'
        : 'O sistema organiza as receitas e despesas pela data e pela competencia da atividade, sem periodos salariais.';
    $('periodCards').hidden = !config.usesSalaryPeriods;
    $('cltSpreadsheetCard').hidden = !config.usesSalaryPeriods;
    $('periodCalendarCard').hidden = !config.usesSalaryPeriods;
    $('agendaPeriodOverview').hidden = !config.usesSalaryPeriods;
    $('transactionPeriodField').hidden = !config.usesSalaryPeriods;
    $('incomePeriodField').hidden = !config.usesSalaryPeriods;
    $('payslipPeriodField').hidden = !config.usesSalaryPeriods;
    $('importPeriodField').hidden = !config.usesSalaryPeriods;
    $('transactionPeriod').value = 'dia20';
    $('incomePeriod').value = 'dia20';
    $('payslipPeriod').value = 'dia20';
    $('importPeriod').value = 'dia20';
    if (!config.usesSalaryPeriods) {
        $('calendarNote').textContent = `Competencia ${monthLabel(state.month)}: lancamentos organizados pela data, sem periodos salariais.`;
        $('importPeriodHelp').textContent = 'Para Pix e débito, confirme apenas a data do pagamento. O sistema não divide o caixa entre Dia 20 e 5º dia útil; compras no cartão serão vinculadas pela fatura, parcelas e cartão cadastrado.';
    } else {
        $('importPeriodHelp').textContent = 'Para Pix e débito, confirme em cada movimento a data do pagamento e se o saldo usado veio do Dia 20 ou do 5º dia útil. Compras no cartão serão vinculadas automaticamente pelo final do cartão cadastrado.';
    }
    $('profileDashboard').hidden = !config.showProfessionalDashboard;
    $('adminUserManagement').hidden = !user.isAdmin;
    if (user.isAdmin) loadAdminUsers();
    $('professionalTab').hidden = !config.showProfessionalDashboard;
    $('profileContext').hidden = true;
    if (config.showProfessionalDashboard) {
        $('profileDashboardTitle').textContent = config.dashboardTitle;
        $('profileDashboardDescription').textContent = config.dashboardDescription;
        $('profileMetricOneLabel').textContent = config.metricLabels[0];
        $('profileMetricTwoLabel').textContent = config.metricLabels[1];
        $('profileMetricThreeLabel').textContent = config.metricLabels[2];
    }

    const payslipButton = document.querySelector('[data-tab="payslips"]');
    if (payslipButton) payslipButton.hidden = !config.showPayslip;
    if (!config.showPayslip && $('panel-payslips').classList.contains('is-active')) activateTab('dashboard');
    if (!config.showProfessionalDashboard && $('panel-professional').classList.contains('is-active')) activateTab('dashboard');
    if (config.showProfessionalDashboard) {
        $('professionalPanelTitle').textContent = config.dashboardTitle.replace(/^(Dashboard|Financeiro)\s+/, '') + ' | Patrimonio, impostos e operacao';
        $('professionalPanelDescription').textContent = config.dashboardDescription + ' Cadastre seus bens e acompanhe as obrigacoes em um unico lugar.';
    }
}

async function loadAdminUsers() {
    const list = $('adminUserList');
    if (!list || !state.user?.isAdmin) return;
    try {
        const data = await api('api/auth?action=admin_users');
        list.innerHTML = (data.users || []).map(user => `
            <article class="admin-user-row">
                <span class="admin-user-avatar">${html((user.name || '?').slice(0, 1).toUpperCase())}</span>
                <div><strong>${html(user.name)}</strong><small>@${html(user.username || 'sem usuario')} · ${html(user.email)}</small></div>
                <span class="admin-user-state ${user.profileCompleted ? 'is-ready' : ''}">${user.isAdmin ? 'Administrador' : (user.profileCompleted ? 'Perfil pronto' : 'Aguardando perfil')}</span>
            </article>
        `).join('') || '<p class="muted">Nenhum usuário cadastrado.</p>';
    } catch (error) {
        list.innerHTML = `<p class="muted">${html(error.message || 'Não foi possível carregar os usuários.')}</p>`;
    }
}

function renderBusinessModule(name) {
    const module = BUSINESS_MODULES[name] || BUSINESS_MODULES.cash;
    state.businessModule = name;
    $('businessModuleIcon').innerHTML = `<svg><use href="#sf-${module.icon}"></use></svg>`;
    $('businessModuleEyebrow').textContent = module.eyebrow;
    $('businessModuleTitle').textContent = module.title;
    $('businessModuleDescription').textContent = module.description;
    $('businessModuleStatus').textContent = module.status;
    $('businessModuleGrid').innerHTML = module.cards.map(card => `
        <article class="business-module-tile">
            <span class="business-module-tile-mark"><svg><use href="#sf-${module.icon}"></use></svg></span>
            <div><h3>${html(card.title)}</h3><p>${html(card.text)}</p></div>
            <button class="secondary-btn business-module-action" type="button" data-business-target="${html(card.target)}">${html(card.action)}</button>
        </article>
    `).join('');
}

function activateBusinessTarget(target) {
    if (!target) return;
    if (target.startsWith('business:')) {
        activateBusinessModule(target.slice('business:'.length));
        return;
    }
    activateTab(target);
}

function activateBusinessModule(name) {
    if (!profileConfig().showProfessionalDashboard) return;
    if (typeof window.openProfessionalModule === 'function' && window.openProfessionalModule(name)) return;
    const button = document.querySelector(`.professional-nav-item[data-business-module="${CSS.escape(name)}"]`);
    document.querySelectorAll('.professional-nav-item').forEach(item => item.classList.remove('is-active'));
    if (button) button.classList.add('is-active');
    if (name === 'professional') {
        activateTab('professional');
        return;
    }
    $('panel-business-module').hidden = false;
    renderBusinessModule(name);
    activateTab('business-module');
}

function onboardingAnswers() {
    return {
        mainActivity: $('onboardingMainActivity').value,
        receivesSalary: $('onboardingReceivesSalary').value,
        hasCnpj: $('onboardingHasCnpj').value,
        sellsProducts: $('onboardingSellsProducts').value,
        hasVehicles: $('onboardingHasVehicles').value,
        hasProperties: $('onboardingHasProperties').value,
        taxControl: $('onboardingTaxControl').value,
    };
}

function inferProfileTypeClient(answers) {
    const activity = answers.mainActivity || '';
    const hasCnpj = answers.hasCnpj === 'sim';
    const sellsProducts = answers.sellsProducts === 'sim';
    const receivesSalary = answers.receivesSalary === 'sim' || activity === 'clt';
    if (receivesSalary && !sellsProducts && activity !== 'services') return 'clt';
    if (sellsProducts || activity === 'commerce') return hasCnpj ? 'comerciante_pj' : 'comerciante_pf';
    if (activity === 'services' || hasCnpj) return 'pj';
    return 'clt';
}

function updateOnboardingDescription() {
    const answers = onboardingAnswers();
    const config = profileConfig(inferProfileTypeClient(answers));
    $('onboardingProfileDescription').textContent = `Perfil identificado provisoriamente: ${config.label}. ${config.description} O sistema confirmara esta configuracao ao iniciar o Financeiro.`;
}

function showProfileOnboardingIfNeeded() {
    if (state.user?.profileCompleted) return;
    $('onboardingMainActivity').value = 'clt';
    $('onboardingReceivesSalary').value = 'sim';
    $('onboardingHasCnpj').value = 'nao';
    $('onboardingSellsProducts').value = 'nao';
    $('onboardingHasVehicles').value = 'nao';
    $('onboardingHasProperties').value = 'nao';
    $('onboardingTaxControl').value = 'nao';
    updateOnboardingDescription();
    $('profileOnboarding').hidden = false;
}

function showForcedPasswordChangeIfNeeded() {
    const overlay = $('forcePasswordOverlay');
    if (!overlay || !state.user?.mustChangePassword) return;
    overlay.hidden = false;
    window.setTimeout(() => $('forceCurrentPassword')?.focus(), 50);
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
    if (data.user) {
        state.user = data.user;
        applyProfileConfiguration();
    }
    renderDashboard();
    if (profileConfig().usesSalaryPeriods) {
        await loadYearCalendar();
    } else {
        $('yearCalendar').innerHTML = '';
    }
}

async function loadYearCalendar() {
    const year = Number((state.month || currentMonth()).slice(0, 4));
    const data = await api(`api/finance?action=calendar&year=${year}`);
    $('yearCalendar').innerHTML = (data.calendar || []).map(item => {
        const p20 = item.periods.find(p => p.type === 'dia20');
        const p5 = item.periods.find(p => p.type === 'quinto_util');
        const health = item.health || {};
        const allowedLevels = ['green', 'yellow', 'red', 'empty'];
        const level = allowedLevels.includes(health.status) ? health.status : 'empty';
        const statusLabel = health.label || 'Sem dados';
        const hasIncome = Number(health.income || 0) > 0;
        const usagePercent = Math.max(0, Math.min(100, Number(health.usagePercent || 0)));
        const usageText = hasIncome
            ? `${Number(health.usagePercent || 0).toLocaleString('pt-BR')}% da receita usada`
            : 'Aguardando entradas';
        const closeText = hasIncome
            ? `Fechamento: ${money(health.available || 0)}`
            : 'Fechamento sem dados';
        const currentClass = item.month === state.month ? ' is-current' : '';
        return `
            <div class="calendar-month calendar-month--${level}${currentClass}" title="${html(statusLabel)} — ${html(usageText)}">
                <div class="calendar-month-head"><strong>${html(monthLabel(item.month))}</strong><span class="calendar-month-status"><i></i>${html(statusLabel)}</span></div>
                <div class="calendar-month-meter"><span style="width: ${usagePercent}%"></span></div>
                <span class="calendar-month-usage">${html(usageText)}</span>
                <span class="calendar-month-close">${html(closeText)}</span>
                <span>Dia 20: ${dateBr(p20?.dueDate)}</span>
                <span>5º dia útil: ${dateBr(p5?.dueDate)}</span>
            </div>
        `;
    }).join('');
}

function renderDashboard() {
    const data = state.dashboard || {};
    const summary = data.summary || {};
    $('heroMonth').textContent = `Competencia ${monthLabel(data.month)}`;
    $('agendaMonthLabel').textContent = monthLabel(data.month);
    $('availableBalance').textContent = money(summary.availableBalance);
    $('incomeTotal').textContent = money(summary.income);
    $('expenseTotal').textContent = money(summary.expenses);
    $('pendingTotal').textContent = money(summary.pending);
    $('savedTotal').textContent = money(summary.savedGoals);
    renderSpendingHealth(summary);
    $('balanceNote').textContent = profileConfig().usesSalaryPeriods
        ? `${summary.usagePercent || 0}% da renda liquida usada | ${summary.savingPercent || 0}% reservado.`
        : `${summary.usagePercent || 0}% das despesas sobre as receitas | ${summary.savingPercent || 0}% reservado.`;
    $('calendarNote').textContent = profileConfig().usesSalaryPeriods
        ? `Competência ${monthLabel(data.month)}: Dia 20 e 5º dia útil do mês seguinte.`
        : `Competencia ${monthLabel(data.month)}: lancamentos organizados pela data, sem periodos salariais.`;
    renderProfileDashboard(data);
    renderCltSpreadsheet(data);

    renderPeriods(data);
    renderCategoryChart(data);
    renderBalanceLimitCharts(data);
    renderDashboardCards(data.cards || [], data.cardSummary || []);
    renderTransactions(data.transactions || []);
    renderIncomes(data.incomes || []);
    renderPayslips(data.incomes || []);
    renderCards(data.cards || [], data.cardSummary || []);
    renderGoals(data.goals || []);
    renderProfessional(data);
    renderAssistantBase(data);
}

function renderSpendingHealth(summary) {
    const income = Number(summary.income || 0);
    const expenses = Number(summary.expenses || 0);
    const pending = Number(summary.pending || 0);
    const saved = Number(summary.savedGoals || 0);
    const usage = income > 0 ? expenses / income : 0;
    const usagePercent = income > 0 ? Math.round(usage * 100) : 0;
    const economyPercent = income > 0 ? Math.max(0, Math.min(100, Math.round((1 - usage) * 100))) : 0;
    const traffic = $('spendingTraffic');
    const status = $('spendingStatus');
    const advice = $('spendingAdvice');
    const fill = $('econometerFill');
    const percent = $('econometerPercent');
    const economyAdvice = $('econometerAdvice');
    if (!traffic || !status || !advice || !fill || !percent || !economyAdvice) return;

    document.querySelectorAll('#spendingTraffic .traffic-light').forEach(light => light.classList.remove('is-active'));
    if (income <= 0) {
        status.textContent = 'Aguardando dados';
        advice.textContent = 'Registre uma receita líquida para ativar a leitura dos gastos.';
        percent.textContent = '0% livre';
        economyAdvice.textContent = 'O Economizômetro aparece depois da primeira entrada registrada.';
        fill.style.width = '0%';
        return;
    }

    const level = usage <= 0.6 ? 'green' : usage <= 0.8 ? 'yellow' : 'red';
    const activeLight = document.querySelector(`#spendingTraffic .traffic-light.${level}`);
    if (activeLight) activeLight.classList.add('is-active');
    status.textContent = level === 'green' ? 'Indo bem' : level === 'yellow' ? 'Atenção' : 'Economize';
    if (level === 'green') {
        advice.textContent = `Você usou ${usagePercent}% da receita. Mantenha o ritmo e preserve parte do saldo.`;
    } else if (level === 'yellow') {
        advice.textContent = `Você usou ${usagePercent}% da receita. Revise gastos variáveis e aumente a reserva.`;
    } else {
        advice.textContent = `Você usou ${usagePercent}% da receita. Corte gastos não essenciais e evite novas parcelas.`;
    }
    if (pending > 0) advice.textContent += ` Há ${money(pending)} em pendências.`;
    fill.style.width = `${economyPercent}%`;
    percent.textContent = `${economyPercent}% livre`;
    economyAdvice.textContent = saved > 0
        ? `${money(saved)} já está guardado em metas. Continue protegendo o saldo livre.`
        : economyPercent >= 20
            ? `${money(Math.max(0, income - expenses))} ainda pode virar reserva ou meta.`
          : 'Pouco saldo livre: priorize despesas essenciais e reduza variáveis.';
}

function renderProfileDashboard(data) {
    const config = profileConfig();
    if (!config.showProfessionalDashboard) return;
    const summary = data.summary || {};
    $('profileMetricOne').textContent = money(summary.income);
    $('profileMetricTwo').textContent = money(summary.expenses);
    $('profileMetricThree').textContent = money(summary.availableBalance);
}

function paymentMethodLabel(item) {
    const method = String(item.paymentMethod || '').toLowerCase();
    if (item.type === 'cartao_credito' || method === 'credito' || item.cardId) return 'Cartao';
    return ({ pix: 'Pix', debito: 'Debito', dinheiro: 'Dinheiro', boleto: 'Boleto', transferencia: 'Transferencia' })[method] || 'Outro';
}

function renderCltSpreadsheet(data) {
    const card = $('cltSpreadsheetCard');
    if (!card) return;
    if (!profileConfig().usesSalaryPeriods) {
        card.hidden = true;
        $('cltSpreadsheetBody').innerHTML = '';
        return;
    }
    card.hidden = false;
    $('cltSpreadsheetPeriod').textContent = `Competencia ${monthLabel(data.month)}`;
    const metrics = { card: 0, pix: 0, debit: 0, cash: 0 };
    const expenses = (data.transactions || []).filter(item => item.type !== 'entrada');
    expenses.forEach(item => {
        const method = paymentMethodLabel(item).toLowerCase();
        if (method === 'cartao') metrics.card += Number(item.amount || 0);
        else if (method === 'pix') metrics.pix += Number(item.amount || 0);
        else if (method === 'debito') metrics.debit += Number(item.amount || 0);
        else metrics.cash += Number(item.amount || 0);
    });
    $('cltCardExpenses').textContent = money(metrics.card);
    $('cltPixExpenses').textContent = money(metrics.pix);
    $('cltDebitExpenses').textContent = money(metrics.debit);
    $('cltCashExpenses').textContent = money(metrics.cash);

    $('cltSpreadsheetBody').innerHTML = expenses.map(item => {
        const category = item.category || 'Outros';
        const visual = categoryVisual(category);
        const method = paymentMethodLabel(item);
        const statusLabel = item.status === 'pago' ? 'Pago' : item.status === 'pendente' ? 'Pendente' : 'Programado';
        const statusClass = item.status === 'pago' ? 'success' : item.status === 'pendente' ? 'danger' : 'warn';
        const installmentCount = Number(item.installmentCount || 1);
        const installment = installmentCount > 1 ? `${Number(item.installmentNumber || 1)}/${installmentCount}` : 'Unica';
        const cardRef = method === 'Cartao'
            ? `${html(item.cardNickname || 'Cartao')}${item.cardLast4 ? ` final ${html(item.cardLast4)}` : ''}`
            : '--';
        return `
            <tr>
                <td>${dateBr(item.movementDate)}${item.movementTime ? `<br><small>${html(item.movementTime)}</small>` : ''}</td>
                <td><span class="spreadsheet-description"><span class="category-picture category-picture-small category-picture-${visual.className}" role="img" aria-label="${html(visual.label)}">${visual.icon}</span><span class="spreadsheet-description-text"><strong>${html(item.description || 'Lancamento')}</strong><span>${html(category)}</span></span></span></td>
                <td><span class="spreadsheet-method">${html(method)}</span></td>
                <td><span class="spreadsheet-card-ref">${cardRef}</span></td>
                <td><span class="spreadsheet-period-ref">${html(periodText(item.periodType))}</span></td>
                <td><span class="spreadsheet-installment">${html(installment)}</span></td>
                <td><span class="badge ${statusClass}">${html(statusLabel)}</span></td>
                <td class="amount-cell">${money(item.amount)}</td>
            </tr>
        `;
    }).join('') || '<tr><td class="clt-spreadsheet-empty" colspan="8">Nenhum gasto registrado nesta competencia.</td></tr>';
}

function professionalTaxLabel(value) {
    return ({
        das_mei: 'DAS / MEI',
        simples_nacional: 'Simples Nacional',
        iss: 'ISS',
        icms: 'ICMS',
        irpj_csll: 'IRPJ / CSLL',
        inss_prolabore: 'INSS / pro-labore',
        iptu: 'IPTU',
        ipva: 'IPVA',
        licenciamento: 'Licenciamento',
        outro: 'Outro',
    })[value] || 'Outro';
}

function professionalAssetTypeLabel(value) {
    return value === 'property' ? 'Casa / imovel' : 'Veiculo';
}

function professionalAssetStatusLabel(value) {
    return ({ ativo: 'Ativo', quitado: 'Quitado', em_manutencao: 'Em manutencao', inativo: 'Inativo' })[value] || 'Ativo';
}

function professionalStatusClass(value) {
    return value === 'pago' || value === 'quitado' ? 'success' : value === 'pendente' || value === 'em_manutencao' ? 'warn' : '';
}

function renderProfessional(data) {
    if (!profileConfig().showProfessionalDashboard) return;
    const professional = data.professional || {};
    const professionalSummary = professional.summary || {};
    const summary = data.summary || {};
    const taxTotal = Number(professionalSummary.taxTotal || 0);
    const taxPaid = Number(professionalSummary.taxPaid || 0);
    const taxPending = Number(professionalSummary.taxPending || 0);
    const operatingResult = Number(summary.income || 0) - Number(summary.expenses || 0) - taxTotal;
    const taxPercent = taxTotal > 0 ? Math.round((taxPaid / taxTotal) * 100) : 0;
    const taxMargin = Number(summary.income || 0) > 0 ? (100 - (taxTotal / Number(summary.income || 1) * 100)) : 0;

    $('professionalAssetValue').textContent = money(professionalSummary.assetValue);
    $('professionalAssetCount').textContent = `${Number(professionalSummary.assetCount || 0)} bens | ${Number(professionalSummary.vehicleCount || 0)} veiculos | ${Number(professionalSummary.propertyCount || 0)} imoveis`;
    $('professionalMonthlyCost').textContent = money(professionalSummary.monthlyCost);
    $('professionalTaxTotal').textContent = money(taxTotal);
    $('professionalTaxStatus').textContent = taxTotal > 0 ? `${money(taxPaid)} pago | ${money(taxPending)} pendente` : 'Nenhum imposto lancado';
    $('professionalOperatingResult').textContent = money(operatingResult);
    $('professionalYearTaxTotal').textContent = money(professionalSummary.yearTaxTotal);
    $('professionalAnnualAssetTax').textContent = money(professionalSummary.annualAssetTax);
    $('professionalTaxMargin').textContent = `${taxMargin.toLocaleString('pt-BR', { maximumFractionDigits: 1 })}%`;
    $('professionalTaxMeterFill').style.width = `${Math.max(0, Math.min(100, taxPercent))}%`;
    $('professionalTaxMeterPercent').textContent = `${taxPercent}% pago`;
    $('professionalTaxPaidLabel').textContent = `Pago ${money(taxPaid)}`;
    $('professionalTaxPendingLabel').textContent = `Pendente ${money(taxPending)}`;

    const nextTax = professional.nextTax;
    $('professionalNextTaxBadge').textContent = nextTax
        ? `Proximo: ${professionalTaxLabel(nextTax.taxType)} em ${dateBr(nextTax.dueDate)}`
        : 'Sem proximo vencimento';

    $('professionalAssetList').innerHTML = (professional.assets || []).map(asset => `
        <article class="list-item professional-list-item">
            <div class="list-row"><strong class="professional-item-title">${uiIcon(asset.assetType === 'property' ? 'home' : 'cat-transport')}${html(asset.name)}</strong><span class="badge ${professionalStatusClass(asset.status)}">${html(professionalAssetStatusLabel(asset.status))}</span></div>
            <span class="muted">${html(professionalAssetTypeLabel(asset.assetType))}${asset.identifier ? ` | ${html(asset.identifier)}` : ''}</span>
            <span class="muted">Valor ${money(asset.estimatedValue)} | custo mensal ${money(asset.monthlyCost)} | IPTU/IPVA anual ${money(asset.annualTax)}</span>
            ${asset.description ? `<span class="muted">${html(asset.description)}</span>` : ''}
            <div class="list-row"><span class="muted">Proximo vencimento: ${dateBr(asset.nextDueDate)}</span><span><button class="tiny-btn" type="button" onclick="editProfessionalAsset(${Number(asset.id)})">Editar</button><button class="tiny-btn" type="button" onclick="deleteProfessionalAsset(${Number(asset.id)})">Excluir</button></span></div>
        </article>
    `).join('') || '<p class="muted">Nenhum veiculo ou imovel cadastrado.</p>';

    $('professionalTaxList').innerHTML = (professional.taxes || []).map(tax => `
        <article class="list-item professional-list-item">
            <div class="list-row"><strong>${html(professionalTaxLabel(tax.taxType))}</strong><b>${money(tax.amount)}</b></div>
            <span class="muted">Competencia ${html(monthLabel(tax.competenceMonth))} | vencimento ${dateBr(tax.dueDate)}</span>
            <span class="badge ${professionalStatusClass(tax.status)}">${html(tax.status === 'pago' ? 'Pago' : tax.status === 'programado' ? 'Programado' : 'Pendente')}</span>
            ${tax.paidDate ? `<span class="muted">Pago em ${dateBr(tax.paidDate)}</span>` : ''}
            ${tax.notes ? `<span class="muted">${html(tax.notes)}</span>` : ''}
            <div class="list-row"><span></span><span><button class="tiny-btn" type="button" onclick="editProfessionalTax(${Number(tax.id)})">Editar</button><button class="tiny-btn" type="button" onclick="deleteProfessionalTax(${Number(tax.id)})">Excluir</button></span></div>
        </article>
    `).join('') || '<p class="muted">Nenhum imposto registrado nesta competencia.</p>';
}

function renderPeriods(data) {
    if (!profileConfig().usesSalaryPeriods) {
        $('periodCards').innerHTML = '';
        return;
    }
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

function uiIcon(name) {
    return `<svg class="ui-svg-icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><use href="#sf-${name}"></use></svg>`;
}

function categoryVisual(category) {
    const key = String(category || 'Outros')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
    const visuals = [
        ['pensao', 'cat-pension', 'Pensao', 'pensao'],
        ['mercado', 'cat-market', 'Mercado', 'mercado'],
        ['transporte', 'cat-transport', 'Transporte', 'transporte'],
        ['saude', 'cat-health', 'Saude', 'saude'],
        ['alimentacao', 'cat-food', 'Alimentacao', 'alimentacao'],
        ['moradia', 'cat-home', 'Moradia', 'moradia'],
        ['vestuario', 'cat-clothes', 'Vestuario', 'vestuario'],
        ['eletronicos', 'cat-electronics', 'Eletronicos', 'eletronicos'],
        ['educacao', 'cat-education', 'Educacao', 'educacao'],
        ['lazer', 'cat-leisure', 'Lazer', 'lazer'],
        ['assinaturas', 'cat-subscriptions', 'Assinaturas', 'assinaturas'],
        ['pets', 'cat-pets', 'Pets', 'pets'],
        ['beleza', 'cat-beauty', 'Beleza', 'beleza'],
        ['cartao', 'cat-card', 'Cartao', 'cartao'],
        ['receita', 'cat-income', 'Receita', 'receita'],
    ];
    const found = visuals.find(([term]) => key.includes(term));
    return found
        ? { icon: uiIcon(found[1]), label: found[2], className: found[3] }
        : { icon: uiIcon('cat-other'), label: 'Outros', className: 'outros' };
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
    $('balanceLimitChartNote').textContent = profileConfig().usesSalaryPeriods
        ? `Competencia ${monthLabel(data.month)} | Cartoes: ${money(summary.cardUsedTotal || 0)} usados de ${money(summary.cardLimitTotal || 0)} (${Number(summary.cardUsagePercent || 0).toLocaleString('pt-BR')}%).`
        : `Por data e competencia | Cartoes: ${money(summary.cardUsedTotal || 0)} usados de ${money(summary.cardLimitTotal || 0)} (${Number(summary.cardUsagePercent || 0).toLocaleString('pt-BR')}%).`;

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
                    <span class="muted transaction-category"><span class="category-picture category-picture-small category-picture-${visual.className}" role="img" aria-label="${html(visual.label)}">${visual.icon}</span><span>${dateBr(item.movementDate)}${item.movementTime ? ' as ' + html(item.movementTime) : ''} | ${html(category)}${profileConfig().usesSalaryPeriods ? ' | ' + periodText(item.periodType) : ''}</span></span>
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
    return value === 'quinto_util' ? '5º dia útil' : 'Dia 20';
}

function incomePeriodLabel(value) {
    return profileConfig().usesSalaryPeriods ? periodText(value) : 'Recebimento';
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
    if (!profileConfig().usesSalaryPeriods) {
        note.textContent = isCreditPurchaseForm()
            ? 'Compra no credito: o lancamento sera organizado pela data da compra, pela fatura e pelas parcelas do cartao.'
            : 'Pix, débito, dinheiro e demais lançamentos serão organizados pela data, sem divisão entre Dia 20 e 5º dia útil.';
        return;
    }
    if (!isCreditPurchaseForm()) {
        note.textContent = 'Pix, débito e dinheiro entram no mês atual. No cartão, até o 5º dia útil fica no mês atual; depois dele, a primeira parcela ou o valor total vai para o mês seguinte.';
        return;
    }

    const date = $('transactionDate').value || todayIso();
    const cutoff = fifthBusinessDayIso(date);
    if (creditPurchaseAfterCutoff(date)) {
        const scheduledDate = shiftIsoMonth(date, 1);
        note.textContent = `Compra no crédito feita em ${dateBr(date)}, após o 5º dia útil (${dateBr(cutoff)}): a primeira parcela ou o valor total será lançado em ${dateBr(scheduledDate)}. As demais parcelas seguem nos meses seguintes.`;
    } else {
        note.textContent = `Compra no crédito feita em ${dateBr(date)} entra na competência atual até o 5º dia útil (${dateBr(cutoff)}). Depois dele, vai para o mesmo dia do mês seguinte.`;
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
    const confirmed = confirm('Isso vai excluir todos os lancamentos, recebimentos, cartoes, metas, importacoes, clientes, produtos, caixas, categorias, documentos fiscais, veiculos, imoveis e impostos desta conta. A conta de acesso sera preservada. Deseja continuar?');
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
        const isProfessionalIncome = !profileConfig().usesSalaryPeriods && !isPayslip;
        const detail = isPayslip
            ? 'Holerite | ganho liquido informado diretamente'
            : isProfessionalIncome
                ? `Faturamento / recebimento liquido ${money(item.netAmount)} | sem descontos`
            : `Bruto ${money(item.grossSalary)} | INSS ${money(item.inssDiscount)} | VT ${money(item.transportDiscount)}`;
        return `
            <article class="list-item">
            <div class="list-row"><strong>${incomePeriodLabel(item.periodType)}</strong><b>${money(item.netAmount)}</b></div>
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
            <div class="list-row"><strong>${html(incomePeriodLabel(item.periodType))}</strong><b>${money(item.netAmount)}</b></div>
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
    const noSalaryPeriod = !profileConfig().usesSalaryPeriods;
    const noDiscount = noSalaryPeriod || $('incomePeriod').value === 'dia20';
    const transportActive = $('transportActive');
    const transportManual = $('transportManual');
    const otherDiscounts = $('otherDiscounts');
    transportActive.disabled = noDiscount;
    transportManual.disabled = noDiscount;
    otherDiscounts.disabled = noDiscount;
    if (noSalaryPeriod) {
        transportActive.checked = false;
        transportManual.value = '';
        otherDiscounts.value = '';
        $('incomeDiscountNote').textContent = 'Perfil profissional: recebimentos organizados pela data, sem periodos salariais.';
    } else if (noDiscount) {
        transportActive.checked = false;
        transportManual.value = '';
        otherDiscounts.value = '';
        $('incomeDiscountNote').textContent = 'Dia 20: INSS, vale-transporte e outros descontos estao desativados.';
    } else {
        $('incomeDiscountNote').textContent = '5º dia útil: descontos permitidos conforme os campos acima.';
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

function clearProfessionalAssetForm() {
    $('professionalAssetForm').reset();
    $('professionalAssetId').value = '';
    $('professionalAssetDueDate').value = '';
    $('professionalAssetStatus').value = 'ativo';
}

function editProfessionalAsset(id) {
    const asset = (state.dashboard?.professional?.assets || []).find(row => Number(row.id) === Number(id));
    if (!asset) return;
    $('professionalAssetId').value = asset.id;
    $('professionalAssetType').value = asset.assetType;
    $('professionalAssetName').value = asset.name;
    $('professionalAssetIdentifier').value = asset.identifier || '';
    $('professionalAssetValueInput').value = String(asset.estimatedValue || 0).replace('.', ',');
    $('professionalAssetMonthlyCost').value = String(asset.monthlyCost || 0).replace('.', ',');
    $('professionalAssetAnnualTax').value = String(asset.annualTax || 0).replace('.', ',');
    $('professionalAssetDueDate').value = asset.nextDueDate || '';
    $('professionalAssetStatus').value = asset.status || 'ativo';
    $('professionalAssetDescription').value = asset.description || '';
    activateTab('professional');
}

async function deleteProfessionalAsset(id) {
    if (!confirm('Excluir este veiculo ou imovel?')) return;
    state.dashboard = await financePost('delete_professional_asset', { id });
    renderDashboard();
}

function clearProfessionalTaxForm() {
    $('professionalTaxForm').reset();
    $('professionalTaxId').value = '';
    $('professionalTaxCompetence').value = state.month || currentMonth();
    $('professionalTaxDueDate').value = todayIso();
    $('professionalTaxStatusInput').value = 'pendente';
    $('professionalTaxPaidDate').value = '';
}

function editProfessionalTax(id) {
    const tax = (state.dashboard?.professional?.taxes || []).find(row => Number(row.id) === Number(id));
    if (!tax) return;
    $('professionalTaxId').value = tax.id;
    $('professionalTaxType').value = tax.taxType;
    $('professionalTaxCompetence').value = tax.competenceMonth;
    $('professionalTaxDueDate').value = tax.dueDate;
    $('professionalTaxAmount').value = String(tax.amount || 0).replace('.', ',');
    $('professionalTaxStatusInput').value = tax.status || 'pendente';
    $('professionalTaxPaidDate').value = tax.paidDate || '';
    $('professionalTaxNotes').value = tax.notes || '';
    activateTab('professional');
}

async function deleteProfessionalTax(id) {
    if (!confirm('Excluir este imposto?')) return;
    state.dashboard = await financePost('delete_professional_tax', { id });
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
    const needsBalanceChoice = profileConfig().usesSalaryPeriods && !card && ['pix', 'debito'].includes(method);

    if (period) {
        period.required = needsBalanceChoice;
        if (needsBalanceChoice && resetPeriod) period.value = '';
        if (!needsBalanceChoice && !period.value) period.value = 'dia20';
    }

    const help = row.querySelector('[data-import-help]');
    if (!help) return;
    if (!profileConfig().usesSalaryPeriods) {
        help.textContent = card
            ? 'Compra no cartao: confira o final 4 e o cartao cadastrado; o lancamento seguira a fatura e as parcelas.'
            : 'Lançamento organizado pela data do pagamento, sem saldo de Dia 20 ou 5º dia útil.';
    } else if (card) {
        const date = row.querySelector('[data-field="movementDate"]')?.value || '';
        if (creditPurchaseAfterCutoff(date)) {
            const cutoff = fifthBusinessDayIso(date);
            help.textContent = `Compra no cartão após o 5º dia útil (${dateBr(cutoff)}): vinculada pelo final 4 e programada para ${dateBr(shiftIsoMonth(date, 1))}.`;
        } else {
            help.textContent = 'Compra no cartão até o 5º dia útil: confira o final 4 e o cartão cadastrado. Ela será lançada no mês atual.';
        }
    } else if (needsBalanceChoice) {
        help.textContent = 'Pix/débito: selecione a data do pagamento e o saldo de origem usado (Dia 20 ou 5º dia útil).';
    } else {
        help.textContent = `Metodo ${method || 'nao identificado'}: confira a data e o saldo de origem antes de salvar.`;
    }
}

function renderImportPreview() {
    $('importPreview').innerHTML = state.pendingImport.map((item, index) => {
        const card = importItemIsCard(item);
        const usesPeriods = profileConfig().usesSalaryPeriods;
        const needsBalanceChoice = usesPeriods && !card && ['pix', 'debito'].includes(item.paymentMethod);
        const periodValue = needsBalanceChoice && !item.periodTypeConfirmed ? '' : (item.periodType || 'dia20');
        const periodOptions = needsBalanceChoice
            ? [['', 'Selecione o saldo usado'], ['dia20', 'Saldo do Dia 20'], ['quinto_util', 'Saldo do 5º dia útil']]
            : [['dia20', 'Dia 20'], ['quinto_util', '5º dia útil']];
        const helpClass = item.category === 'Pensao' ? 'is-pension' : item.cardMatch === 'matched' ? 'is-ok' : item.cardLast4 ? 'is-alert' : '';
        const helpText = !usesPeriods
            ? (card ? 'Compra no cartão: confira o final 4 e o cartão cadastrado; o lançamento seguirá a fatura e as parcelas.' : 'Lançamento organizado pela data do pagamento, sem saldo de Dia 20 ou 5º dia útil.')
            : item.category === 'Pensao'
            ? 'Pensao identificada e classificada como saida.'
            : card
                ? 'Compra no cartao: confira o final 4 e o cartao cadastrado. Ela sera lancada em contas feitas no cartao.'
                : needsBalanceChoice
                    ? 'Pix/débito: selecione a data do pagamento e o saldo de origem usado (Dia 20 ou 5º dia útil).'
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
                ${usesPeriods ? `<div class="field"><label>${needsBalanceChoice ? 'Saldo de origem *' : 'Saldo de origem'}</label><select class="plain-select" data-field="periodType" ${needsBalanceChoice ? 'required' : ''}>${options(periodValue, periodOptions)}</select></div>` : ''}
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
    const usesPeriods = profileConfig().usesSalaryPeriods;
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
        if (!usesPeriods) {
            item.periodType = 'dia20';
            item.periodTypeConfirmed = true;
        } else if (!isCard && ['pix', 'debito'].includes(item.paymentMethod)) {
            if (!item.periodType) {
                throw new Error('Selecione o saldo de origem (Dia 20 ou 5º dia útil) para cada Pix ou débito.');
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

function exportSummaryPdf() {
    const data = state.dashboard || {};
    if (!data.summary) {
        alert('Aguarde o carregamento do Financeiro para gerar o resumo.');
        return;
    }

    const summary = data.summary || {};
    const user = data.user || state.user || {};
    const generatedAt = new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'medium' }).format(new Date());
    const profileLabel = user.profileLabel || 'Perfil financeiro';
    const table = (headers, rows, empty = 'Nenhum registro no periodo.') => `
        <table>
            <thead><tr>${headers.map(header => `<th>${html(header)}</th>`).join('')}</tr></thead>
            <tbody>${rows || `<tr><td class="empty" colspan="${headers.length}">${html(empty)}</td></tr>`}</tbody>
        </table>
    `;
    const categoryRows = (data.categories || []).map(item => `
        <tr><td>${html(item.name || 'Outros')}</td><td class="amount">${money(item.amount)}</td></tr>
    `).join('');
    const incomeRows = (data.incomes || []).map(item => `
        <tr><td>${html(incomePeriodLabel(item.periodType))}</td><td>${dateBr(item.paymentDate)}</td><td class="amount">${money(item.netAmount)}</td></tr>
    `).join('');
    const movementRows = (data.transactions || []).map(item => `
        <tr><td>${dateBr(item.movementDate)}</td><td>${html(item.description || 'Lancamento')}</td><td>${html(item.category || 'Outros')}</td><td>${html(item.paymentMethod || 'Outro')}</td><td class="amount">${money(item.amount)}</td></tr>
    `).join('');
    const cardSummary = Object.fromEntries((data.cardSummary || []).map(item => [Number(item.id), item]));
    const cardRows = (data.cards || []).map(card => {
        const totals = cardSummary[Number(card.id)] || {};
        return `<tr><td>${html(card.nickname || 'Cartao')}</td><td>${html(card.institution || 'Nao informada')}</td><td>${html(card.brand || 'Nao informada')} - final ${html(card.last4 || '----')}</td><td class="amount">${money(totals.used || 0)} / ${money(card.limitTotal || 0)}</td></tr>`;
    }).join('');
    const goalRows = (data.goals || []).map(item => `
        <tr><td>${html(item.name || 'Meta')}</td><td>${html(monthLabel(item.referenceMonth))}</td><td class="amount">${money(item.savedAmount)} / ${money(item.targetAmount)}</td></tr>
    `).join('');
    const professional = data.professional || {};
    const professionalSummary = professional.summary || {};
    const professionalAssetRows = (professional.assets || []).map(item => `
        <tr><td>${html(item.name || 'Bem')}</td><td>${html(professionalAssetTypeLabel(item.assetType))}</td><td>${html(item.identifier || '--')}</td><td class="amount">${money(item.estimatedValue)}</td></tr>
    `).join('');
    const professionalTaxRows = (professional.taxes || []).map(item => `
        <tr><td>${html(professionalTaxLabel(item.taxType))}</td><td>${dateBr(item.dueDate)}</td><td>${html(item.status === 'pago' ? 'Pago' : item.status === 'programado' ? 'Programado' : 'Pendente')}</td><td class="amount">${money(item.amount)}</td></tr>
    `).join('');
    const professionalSection = profileConfig().showProfessionalDashboard ? `
        <section class="section"><h2>Patrimonio profissional</h2>${table(['Bem', 'Tipo', 'Identificacao', 'Valor estimado'], professionalAssetRows, 'Nenhum bem cadastrado.')}</section>
        <section class="section"><h2>Impostos e obrigacoes</h2>${table(['Imposto', 'Vencimento', 'Status', 'Valor'], professionalTaxRows, 'Nenhum imposto registrado nesta competencia.')}</section>
        <section class="section"><h2>Resumo operacional</h2>${table(['Indicador', 'Valor'], `
            <tr><td>Patrimonio estimado</td><td class="amount">${money(professionalSummary.assetValue)}</td></tr>
            <tr><td>Custos mensais dos bens</td><td class="amount">${money(professionalSummary.monthlyCost)}</td></tr>
            <tr><td>Impostos do mes</td><td class="amount">${money(professionalSummary.taxTotal)}</td></tr>
            <tr><td>Impostos pagos no mes</td><td class="amount">${money(professionalSummary.taxPaid)}</td></tr>
        `)}</section>
    ` : '';
    const cltDetailedRows = profileConfig().usesSalaryPeriods
        ? (data.transactions || []).filter(item => item.type !== 'entrada').map(item => {
            const visual = categoryVisual(item.category || 'Outros');
            const count = Number(item.installmentCount || 1);
            const installment = count > 1 ? `${Number(item.installmentNumber || 1)}/${count}` : 'Unica';
            const cardRef = paymentMethodLabel(item) === 'Cartao'
                ? `${item.cardNickname || 'Cartao'}${item.cardLast4 ? ` final ${item.cardLast4}` : ''}`
                : '--';
            return `<tr><td>${dateBr(item.movementDate)}${item.movementTime ? ' ' + html(item.movementTime) : ''}</td><td>${visual.icon} ${html(item.description || 'Lancamento')}</td><td>${html(item.category || 'Outros')}</td><td>${html(paymentMethodLabel(item))}</td><td>${html(cardRef)}</td><td>${html(periodText(item.periodType))}</td><td>${html(installment)}</td><td class="amount">${money(item.amount)}</td></tr>`;
        }).join('')
        : '';
    const logo = `<svg viewBox="0 0 64 64" aria-hidden="true"><path d="M13 21h38a5 5 0 0 1 5 5v20a5 5 0 0 1-5 5H13a5 5 0 0 1-5-5V26a5 5 0 0 1 5-5Z" fill="currentColor"/><path d="M18 17h28l3 4H15l3-4Z" fill="currentColor"/><circle cx="32" cy="36" r="7" fill="#fff"/></svg>`;
    const iconSprite = document.querySelector('.icon-sprite')?.outerHTML || '';
    const reportWindow = window.open('', '_blank', 'width=960,height=900');
    if (!reportWindow) {
        alert('O navegador bloqueou a janela do PDF. Permita pop-ups para este sistema e tente novamente.');
        return;
    }

    reportWindow.document.open();
    reportWindow.document.write(`<!doctype html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <title>Resumo financeiro - ${html(monthLabel(data.month))}</title>
            <style>
                @page { size: A4; margin: 14mm 12mm 18mm; }
                * { box-sizing: border-box; }
                body { margin: 0; padding: 0 0 14mm; color: #263336; background: #fff; font-family: Inter, "Segoe UI", Arial, sans-serif; font-size: 9.5pt; }
                .report-header { display: flex; align-items: center; gap: 10px; padding-bottom: 10px; border-bottom: 2px solid #2aa9a7; }
                .report-logo { display: grid; place-items: center; width: 34px; height: 34px; color: #218f91; }
                .report-logo svg { width: 100%; height: 100%; }
                .icon-sprite { position: absolute; width: 0; height: 0; overflow: hidden; }
                .ui-svg-icon { width: 18px; height: 18px; vertical-align: middle; }
                h1 { margin: 0; color: #1f5052; font-size: 19pt; letter-spacing: -0.03em; }
                h2 { margin: 15px 0 6px; color: #1f6668; font-size: 12pt; }
                p { margin: 4px 0; color: #657577; line-height: 1.4; }
                .meta { margin-left: auto; color: #5c7072; text-align: right; font-size: 8.5pt; }
                .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 7px; margin-top: 12px; }
                .summary-card { padding: 8px; border: 1px solid #d6e5e4; border-radius: 7px; background: #f4fbfa; }
                .summary-card span { display: block; color: #688183; font-size: 8pt; }
                .summary-card strong { display: block; margin-top: 3px; color: #206c6e; font-size: 12pt; }
                .section { break-inside: avoid; }
                table { width: 100%; margin-top: 5px; border-collapse: collapse; table-layout: fixed; }
                thead { display: table-header-group; }
                th, td { padding: 5px 6px; border-bottom: 1px solid #e3ebeb; text-align: left; vertical-align: top; overflow-wrap: anywhere; }
                th { color: #28686a; background: #e9f6f5; font-size: 8.2pt; }
                td { color: #405355; font-size: 8.5pt; }
                tr { break-inside: avoid; }
                .amount { text-align: right; white-space: nowrap; }
                .empty { color: #819092; text-align: center; }
                .report-footer { position: fixed; right: 0; bottom: 0; left: 0; padding-top: 5px; border-top: 1px solid #d6e5e4; color: #718183; background: #fff; font-size: 8pt; text-align: center; }
                .report-footer::after { content: " - Pagina " counter(page); }
                @media print { .no-print { display: none; } }
            </style>
        </head>
        <body>
            ${iconSprite}
            <header class="report-header">
                <div class="report-logo">${logo}</div>
                <div><h1>Controle Financeiro</h1><p>Resumo financeiro da competencia ${html(monthLabel(data.month))}</p></div>
                <div class="meta"><strong>${html(user.name || 'Usuario')}</strong><br>${html(profileLabel)}</div>
            </header>
            <div class="summary-grid">
                <div class="summary-card"><span>Receita liquida</span><strong>${money(summary.income)}</strong></div>
                <div class="summary-card"><span>Gastos e faturas</span><strong>${money(summary.expenses)}</strong></div>
                <div class="summary-card"><span>Pendencias</span><strong>${money(summary.pending)}</strong></div>
                <div class="summary-card"><span>Saldo disponivel</span><strong>${money(summary.availableBalance)}</strong></div>
            </div>
            <section class="section"><h2>Resumo por categoria</h2>${table(['Categoria', 'Total'], categoryRows)}</section>
            <section class="section"><h2>Movimentos</h2>${table(['Data', 'Descricao', 'Categoria', 'Metodo', 'Valor'], movementRows)}</section>
            ${profileConfig().usesSalaryPeriods ? `<section class="section"><h2>Planilha detalhada de gastos CLT</h2>${table(['Data / hora', 'Estabelecimento', 'Categoria', 'Forma', 'Cartao', 'Periodo', 'Parcela', 'Valor'], cltDetailedRows, 'Nenhum gasto registrado nesta competencia.')}</section>` : ''}
            <section class="section"><h2>Recebimentos</h2>${table([profileConfig().usesSalaryPeriods ? 'Periodo' : 'Tipo', 'Data', 'Liquido'], incomeRows)}</section>
            <section class="section"><h2>Cartoes</h2>${table(['Cartao', 'Instituicao', 'Bandeira / final', 'Uso / limite'], cardRows, 'Nenhum cartao cadastrado.')}</section>
            <section class="section"><h2>Metas</h2>${table(['Meta', 'Competencia', 'Guardado / alvo'], goalRows, 'Nenhuma meta cadastrada.')}</section>
            ${professionalSection}
            <footer class="report-footer">Controle Financeiro - emitido em ${html(generatedAt)}</footer>
        </body>
        </html>`);
    reportWindow.document.close();
    reportWindow.focus();
    setTimeout(() => reportWindow.print(), 450);
}

function activateTab(name) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.toggle('is-active', btn.dataset.tab === name));
    document.querySelectorAll('.professional-nav-item').forEach(btn => {
        if (name !== 'business-module' && name !== 'professional') btn.classList.remove('is-active');
    });
    document.querySelectorAll('.panel').forEach(panel => {
        const isActive = panel.id === `panel-${name}`;
        panel.classList.toggle('is-active', isActive);
        if (isActive) {
            panel.classList.remove('panel-entering');
            requestAnimationFrame(() => panel.classList.add('panel-entering'));
        }
    });
}

document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => activateTab(btn.dataset.tab));
});

document.querySelectorAll('.professional-nav-item[data-business-module]').forEach(btn => {
    btn.addEventListener('click', () => activateBusinessModule(btn.dataset.businessModule));
});

document.addEventListener('click', event => {
    const action = event.target.closest('.business-module-action');
    if (action) activateBusinessTarget(action.dataset.businessTarget);
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
document.querySelectorAll('#onboardingForm select').forEach(select => select.addEventListener('change', updateOnboardingDescription));

$('onboardingForm').addEventListener('submit', async event => {
    event.preventDefault();
    try {
        $('onboardingStatus').textContent = 'Configurando seu Financeiro...';
        const data = await api('api/auth', {
            method: 'POST',
            body: JSON.stringify({
                action: 'save_profile',
                name: state.user?.name || $('profileName').value,
                initialSetup: true,
                answers: onboardingAnswers(),
            }),
        });
        state.user = data.user;
        $('profileOnboarding').hidden = true;
        applyProfileConfiguration();
        await loadDashboard();
    } catch (error) {
        $('onboardingStatus').textContent = error.message || 'Nao foi possivel configurar o perfil.';
    }
});

$('forcePasswordForm').addEventListener('submit', async event => {
    event.preventDefault();
    const status = $('forcePasswordStatus');
    const newPassword = $('forceNewPassword').value;
    if (newPassword !== $('forceConfirmPassword').value) {
        status.textContent = 'A confirmacao da nova senha nao confere.';
        return;
    }
    try {
        status.textContent = 'Atualizando a senha...';
        const data = await api('api/auth', {
            method: 'POST',
            body: JSON.stringify({
                action: 'change_password',
                currentPassword: $('forceCurrentPassword').value,
                newPassword,
            }),
        });
        state.user = data.user;
        $('forcePasswordOverlay').hidden = true;
        $('forcePasswordForm').reset();
    } catch (error) {
        status.textContent = error.message || 'Nao foi possivel atualizar a senha.';
    }
});

$('adminUserForm').addEventListener('submit', async event => {
    event.preventDefault();
    const status = $('adminUserStatus');
    try {
        status.textContent = 'Criando acesso autorizado...';
        await api('api/auth', {
            method: 'POST',
            body: JSON.stringify({
                action: 'create_user',
                name: $('adminUserName').value.trim(),
                username: $('adminUserUsername').value.trim(),
                email: $('adminUserEmail').value.trim(),
                cpf: $('adminUserCpf').value.trim(),
                password: $('adminUserPassword').value,
            }),
        });
        $('adminUserForm').reset();
        status.textContent = 'Usuário criado e autorizado. Ele deverá concluir o perfil no primeiro acesso.';
        await loadAdminUsers();
    } catch (error) {
        status.textContent = error.message || 'Não foi possível criar o usuário.';
    }
});

$('transactionForm').addEventListener('submit', async event => {
    event.preventDefault();
    const savedDashboard = await financePost('save_transaction', {
        transaction: {
            id: Number($('transactionId').value || 0),
            type: $('transactionType').value,
            periodType: profileConfig().usesSalaryPeriods ? $('transactionPeriod').value : 'dia20',
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
            periodType: profileConfig().usesSalaryPeriods ? $('incomePeriod').value : 'dia20',
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
        $('payslipStatus').textContent = 'Holerite salvo. O ganho liquido ja foi incluido no Financeiro.';
        renderDashboard();
    } catch (error) {
        $('payslipStatus').textContent = error.message || 'Nao foi possivel salvar o holerite.';
    }
});

$('profileForm').addEventListener('submit', async event => {
    event.preventDefault();
    try {
        $('profileStatus').textContent = 'Salvando perfil...';
        const data = await api('api/auth', {
            method: 'POST',
            body: JSON.stringify({
                action: 'save_profile',
                name: $('profileName').value,
            }),
        });
        state.user = data.user;
        if (state.dashboard) state.dashboard.user = data.user;
        applyProfileConfiguration();
        renderDashboard();
        $('profileStatus').textContent = data.message || 'Perfil atualizado com sucesso.';
    } catch (error) {
        $('profileStatus').textContent = error.message || 'Nao foi possivel salvar o perfil.';
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

$('professionalAssetForm').addEventListener('submit', async event => {
    event.preventDefault();
    state.dashboard = await financePost('save_professional_asset', {
        asset: {
            id: Number($('professionalAssetId').value || 0),
            assetType: $('professionalAssetType').value,
            name: $('professionalAssetName').value,
            identifier: $('professionalAssetIdentifier').value,
            estimatedValue: parseMoney($('professionalAssetValueInput').value),
            monthlyCost: parseMoney($('professionalAssetMonthlyCost').value),
            annualTax: parseMoney($('professionalAssetAnnualTax').value),
            nextDueDate: $('professionalAssetDueDate').value,
            status: $('professionalAssetStatus').value,
            description: $('professionalAssetDescription').value,
        },
    });
    clearProfessionalAssetForm();
    renderDashboard();
});

$('professionalTaxForm').addEventListener('submit', async event => {
    event.preventDefault();
    const competenceMonth = $('professionalTaxCompetence').value || state.month;
    state.dashboard = await financePost('save_professional_tax', {
        tax: {
            id: Number($('professionalTaxId').value || 0),
            taxType: $('professionalTaxType').value,
            competenceMonth,
            dueDate: $('professionalTaxDueDate').value || todayIso(),
            amount: parseMoney($('professionalTaxAmount').value),
            status: $('professionalTaxStatusInput').value,
            paidDate: $('professionalTaxPaidDate').value,
            notes: $('professionalTaxNotes').value,
        },
    });
    state.month = competenceMonth;
    $('monthFilter').value = competenceMonth;
    clearProfessionalTaxForm();
    renderDashboard();
});

function initInteractiveUi() {
    const formFields = 'input:not([type="hidden"]), select, textarea';
    const syncFieldState = field => {
        const wrapper = field.closest('.field');
        if (!wrapper) return;
        const hasValue = field.type === 'checkbox' || field.type === 'radio'
            ? field.checked
            : String(field.value || '').trim().length > 0;
        wrapper.classList.toggle('is-filled', hasValue);
    };

    document.querySelectorAll(formFields).forEach(syncFieldState);
    document.addEventListener('input', event => {
        if (event.target.matches(formFields)) syncFieldState(event.target);
    });
    document.addEventListener('change', event => {
        if (event.target.matches(formFields)) syncFieldState(event.target);
    });

    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('focusin', () => form.classList.add('is-focused'));
        form.addEventListener('focusout', event => {
            if (!form.contains(event.relatedTarget)) form.classList.remove('is-focused');
        });
    });

    document.addEventListener('click', event => {
        const button = event.target.closest('button.primary-btn, button.secondary-btn, button.danger-btn, button.tiny-btn');
        if (!button || button.disabled) return;
        const rect = button.getBoundingClientRect();
        const ripple = document.createElement('span');
        ripple.className = 'button-ripple';
        ripple.style.left = `${event.clientX - rect.left}px`;
        ripple.style.top = `${event.clientY - rect.top}px`;
        button.appendChild(ripple);
        window.setTimeout(() => ripple.remove(), 600);
    });
}

initInteractiveUi();

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
        clearProfessionalAssetForm();
        clearProfessionalTaxForm();
        updateInstallmentFields();
        updateCreditScheduleNote();
        updateIncomeDiscountFields();
        if (!state.user?.profileCompleted) return;
        await loadDashboard();
    } catch (error) {
        console.error(error);
        window.location.href = 'login';
    }
})();
