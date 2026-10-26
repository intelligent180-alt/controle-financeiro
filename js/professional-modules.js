/* Módulos profissionais: cada opção do menu possui tela, ação e armazenamento próprios. */
(function () {
    const moduleState = { current: null, snapshot: null, search: null, credit: null, loading: false };
    const snapshotKey = {
        clients: 'clients',
        products: 'catalog',
        cash: 'cash',
        wallets: 'cash',
        categories: 'categories',
        payment_methods: 'paymentMethods',
        tax_documents: 'fiscalDocuments',
        schedule: 'schedule',
    };
    const entityLabels = {
        clients: 'cliente',
        products: 'produto ou serviço',
        cash: 'caixa',
        categories: 'categoria',
        payment_methods: 'forma de pagamento',
        tax_documents: 'documento fiscal',
        schedule: 'agendamento',
    };
    const modules = {
        schedule: { eyebrow: 'Operação', icon: 'agenda', title: 'Agendamento', description: 'Organize compromissos, vencimentos e atividades importantes da sua operação.' },
        cash: { eyebrow: 'Operação', icon: 'wallet', title: 'Caixa', description: 'Tenha uma leitura rápida do caixa disponível e dos saldos usados na atividade.' },
        wallets: { eyebrow: 'Financeiro', icon: 'wallet', title: 'Meus caixas', description: 'Separe caixa da empresa, conta corrente e demais saldos operacionais.' },
        clients: { eyebrow: 'Cadastros', icon: 'users', title: 'Clientes', description: 'Mantenha clientes, contatos e informações comerciais organizados.' },
        professional: { eyebrow: 'Gestão profissional', icon: 'briefcase', title: 'Profissional', description: 'Acesse patrimônio, veículos, imóveis, impostos e indicadores avançados.' },
        products: { eyebrow: 'Cadastros', icon: 'box', title: 'Produtos e serviços', description: 'Organize o catálogo da atividade, os itens vendidos e os serviços prestados.' },
        transactions: { eyebrow: 'Financeiro', icon: 'movements', title: 'Lançamentos', description: 'Registre e acompanhe entradas, saídas, compras, cartões e despesas.' },
        categories: { eyebrow: 'Financeiro', icon: 'cat-other', title: 'Categorias', description: 'Classifique receitas e despesas para gerar análises mais claras.' },
        accounts: { eyebrow: 'Financeiro', icon: 'card', title: 'Contas', description: 'Gerencie contas bancárias, cartões e limites usados pela sua atividade.' },
        payment_methods: { eyebrow: 'Financeiro', icon: 'debit', title: 'Formas de pagamento', description: 'Padronize Pix, débito, crédito, dinheiro e outras formas usadas nos lançamentos.' },
        purchases: { eyebrow: 'Análise', icon: 'cat-market', title: 'Compras', description: 'Acompanhe compras de estoque, materiais, fornecedores e despesas da atividade.' },
        registrations: { eyebrow: 'Cadastros', icon: 'document', title: 'Cadastros gerais', description: 'Reúna clientes, produtos, serviços, fornecedores e informações de apoio.' },
        consultation: { eyebrow: 'Análise', icon: 'search', title: 'Consulta', description: 'Encontre rapidamente registros, lançamentos, recebimentos, cartões e obrigações.' },
        permissions: { eyebrow: 'Administração', icon: 'key', title: 'Permissões', description: 'Defina o nível de acesso da equipe aos recursos profissionais.' },
        settings: { eyebrow: 'Administração', icon: 'settings', title: 'Configurações', description: 'Ajuste os dados da atividade e as preferências do Financeiro.' },
        'tax-documents': { eyebrow: 'Fiscal', icon: 'document', title: 'NFSe e NFC-e', description: 'Centralize documentos fiscais e mantenha os registros preparados para conferência.' },
        help: { eyebrow: 'Suporte', icon: 'help', title: 'Ajuda', description: 'Encontre orientações rápidas para usar os módulos profissionais.' },
        administrative: { eyebrow: 'Administração', icon: 'briefcase', title: 'Administrativo', description: 'Acesse a visão de gestão da empresa, patrimônio, impostos e indicadores.' },
    };

    const esc = value => String(value ?? '').replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character]));
    const money = value => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value || 0));
    const dateBr = value => {
        const parts = String(value || '').split('-');
        return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : String(value || '--');
    };
    const monthLabel = value => {
        const date = new Date(`${String(value || '').slice(0, 7)}-01T12:00:00`);
        return Number.isNaN(date.getTime()) ? String(value || '--') : new Intl.DateTimeFormat('pt-BR', { month: 'short' }).format(date).replace('.', '');
    };
    const dateTimeBr = value => {
        const date = new Date(String(value || '').replace(' ', 'T'));
        return Number.isNaN(date.getTime()) ? String(value || '--') : new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(date);
    };
    const el = id => document.getElementById(id);
    const icon = name => `<svg class="pm-svg-icon" aria-hidden="true"><use href="#sf-${name}"></use></svg>`;

    async function request(action, entity = '', record = null, id = 0) {
        const payload = { action };
        if (entity) payload.entity = entity;
        if (record) payload.record = record;
        if (id) payload.id = id;
        if (window.financeState?.month) payload.month = window.financeState.month;
        return window.api('api/professional.php', { method: 'POST', body: JSON.stringify(payload) });
    }

    async function loadSnapshot() {
        moduleState.loading = true;
        try {
            const month = window.financeState?.month || '';
            const query = month ? `&month=${encodeURIComponent(month)}` : '';
            moduleState.snapshot = await window.api(`api/professional.php?action=snapshot${query}`);
            return moduleState.snapshot;
        } finally {
            moduleState.loading = false;
        }
    }

    function setModuleHeader(module) {
        const config = modules[module] || modules.cash;
        el('businessModuleIcon').innerHTML = icon(config.icon);
        el('businessModuleEyebrow').textContent = config.eyebrow;
        el('businessModuleTitle').textContent = config.title;
        el('businessModuleDescription').textContent = config.description;
        el('businessModuleStatus').textContent = 'Módulo profissional conectado aos seus dados.';
    }

    function moduleAction(label, target, primary = false) {
        return `<button class="${primary ? 'primary-btn' : 'secondary-btn'} pm-action" type="button" data-pm-target="${esc(target)}">${esc(label)}</button>`;
    }

    function renderWorkspace(title, description, body) {
        return `<div class="pm-workspace-heading"><div><span class="eyebrow">${esc(title)}</span><p>${esc(description)}</p></div></div>${body}`;
    }

    function renderClients() {
        const rows = moduleState.snapshot?.clients || [];
        return renderWorkspace('Cadastro de clientes', 'Cadastre contatos e mantenha a base comercial sempre atualizada.', `
            <div class="pm-layout">
                <form class="pm-form" data-pm-form="clients">
                    <input type="hidden" name="id">
                    <div class="pm-form-title">Novo cliente</div>
                    <div class="pm-form-grid"><label>Nome completo*<input name="name" required placeholder="Nome ou razão social"></label><label>CPF/CNPJ<input name="document" placeholder="Documento"></label><label>Telefone<input name="phone" placeholder="(00) 00000-0000"></label><label>E-mail<input name="email" type="email" placeholder="cliente@email.com"></label></div>
                    <label>Observações<textarea name="notes" placeholder="Informações comerciais, endereço ou preferências"></textarea></label>
                    <div class="pm-form-actions"><button class="primary-btn" type="submit">Salvar cliente</button><button class="secondary-btn" type="reset">Limpar</button></div>
                </form>
                <div class="pm-record-list"><div class="pm-list-title">Clientes cadastrados <span>${rows.length}</span></div>${rows.length ? rows.map(row => `
                    <article class="pm-record"><div class="pm-record-icon">${icon('users')}</div><div class="pm-record-main"><strong>${esc(row.name)}</strong><small>${esc(row.document || 'Documento não informado')} ${row.phone ? `· ${esc(row.phone)}` : ''}</small></div><div class="pm-record-actions"><button class="tiny-btn" type="button" data-pm-edit="clients" data-pm-id="${row.id}">Editar</button><button class="tiny-btn" type="button" data-pm-delete="clients" data-pm-id="${row.id}">Excluir</button></div></article>`).join('') : '<p class="muted pm-empty">Nenhum cliente cadastrado.</p>'}</div>
            </div>`);
    }

    function renderProducts() {
        const rows = moduleState.snapshot?.catalog || [];
        return renderWorkspace('Catálogo comercial', 'Registre produtos e serviços para ter preço, custo e estoque sob controle.', `
            <div class="pm-layout">
                <form class="pm-form" data-pm-form="products">
                    <input type="hidden" name="id">
                    <div class="pm-form-title">Novo item</div>
                    <div class="pm-form-grid"><label>Tipo<select name="itemType"><option value="product">Produto</option><option value="service">Serviço</option></select></label><label>Nome*<input name="name" required placeholder="Ex.: Instalação elétrica"></label><label>Código<input name="code" placeholder="SKU ou referência"></label><label>Preço de venda<input name="price" inputmode="decimal" placeholder="0,00"></label><label>Custo<input name="cost" inputmode="decimal" placeholder="0,00"></label><label>Estoque<input name="stock" inputmode="decimal" placeholder="0"></label></div>
                    <label>Observações<textarea name="notes" placeholder="Detalhes do item, fornecedor ou prazo"></textarea></label>
                    <div class="pm-form-actions"><button class="primary-btn" type="submit">Salvar item</button><button class="secondary-btn" type="reset">Limpar</button></div>
                </form>
                <div class="pm-record-list"><div class="pm-list-title">Itens cadastrados <span>${rows.length}</span></div>${rows.length ? rows.map(row => `
                    <article class="pm-record"><div class="pm-record-icon">${icon(row.itemType === 'service' ? 'briefcase' : 'box')}</div><div class="pm-record-main"><strong>${esc(row.name)}</strong><small>${row.itemType === 'service' ? 'Serviço' : 'Produto'} · ${money(row.price)}${row.code ? ` · ${esc(row.code)}` : ''}</small></div><div class="pm-record-actions"><button class="tiny-btn" type="button" data-pm-edit="products" data-pm-id="${row.id}">Editar</button><button class="tiny-btn" type="button" data-pm-delete="products" data-pm-id="${row.id}">Excluir</button></div></article>`).join('') : '<p class="muted pm-empty">Nenhum produto ou serviço cadastrado.</p>'}</div>
            </div>`);
    }

    function renderCashModule() {
        const rows = moduleState.snapshot?.cash || [];
        const total = rows.reduce((sum, row) => sum + Number(row.balance || 0), 0);
        return renderWorkspace('Controle de caixas', `Saldo consolidado: ${money(total)}. Cadastre cada origem para separar a operação.`, `
            <div class="pm-layout">
                <form class="pm-form" data-pm-form="cash">
                    <input type="hidden" name="id">
                    <div class="pm-form-title">Novo caixa</div>
                    <div class="pm-form-grid"><label>Nome do caixa*<input name="name" required placeholder="Ex.: Caixa principal"></label><label>Tipo<select name="accountType"><option value="conta_corrente">Conta corrente</option><option value="caixa">Dinheiro físico</option><option value="pix">Conta Pix</option><option value="poupanca">Poupança</option><option value="outro">Outro</option></select></label><label>Saldo inicial<input name="balance" inputmode="decimal" placeholder="0,00"></label></div>
                    <label>Observações<textarea name="notes" placeholder="Banco, agência, responsável ou notas do caixa"></textarea></label>
                    <div class="pm-form-actions"><button class="primary-btn" type="submit">Salvar caixa</button><button class="secondary-btn" type="reset">Limpar</button></div>
                </form>
                <div class="pm-record-list"><div class="pm-list-title">Caixas cadastrados <span>${rows.length}</span></div>${rows.length ? rows.map(row => `
                    <article class="pm-record"><div class="pm-record-icon">${icon('wallet')}</div><div class="pm-record-main"><strong>${esc(row.name)}</strong><small>${esc(row.accountType.replaceAll('_', ' '))} · ${money(row.balance)}</small></div><div class="pm-record-actions"><button class="tiny-btn" type="button" data-pm-edit="cash" data-pm-id="${row.id}">Editar</button><button class="tiny-btn" type="button" data-pm-delete="cash" data-pm-id="${row.id}">Excluir</button></div></article>`).join('') : '<p class="muted pm-empty">Nenhum caixa cadastrado.</p>'}</div>
            </div>`);
    }

    function renderCategories() {
        const rows = moduleState.snapshot?.categories || [];
        return renderWorkspace('Categorias da operação', 'Crie uma classificação consistente para receitas e despesas.', `
            <div class="pm-layout">
                <form class="pm-form" data-pm-form="categories">
                    <input type="hidden" name="id">
                    <div class="pm-form-title">Nova categoria</div>
                    <div class="pm-form-grid"><label>Nome*<input name="name" required placeholder="Ex.: Fornecedores"></label><label>Tipo<select name="kind"><option value="despesa">Despesa</option><option value="receita">Receita</option></select></label><label>Cor<input name="color" type="color" value="#1b9a68"></label></div>
                    <div class="pm-form-actions"><button class="primary-btn" type="submit">Salvar categoria</button><button class="secondary-btn" type="reset">Limpar</button></div>
                </form>
                <div class="pm-record-list"><div class="pm-list-title">Categorias cadastradas <span>${rows.length}</span></div>${rows.length ? rows.map(row => `
                    <article class="pm-record"><span class="pm-color-dot" style="background:${esc(row.color)}"></span><div class="pm-record-main"><strong>${esc(row.name)}</strong><small>${row.kind === 'receita' ? 'Receita' : 'Despesa'}</small></div><div class="pm-record-actions"><button class="tiny-btn" type="button" data-pm-edit="categories" data-pm-id="${row.id}">Editar</button><button class="tiny-btn" type="button" data-pm-delete="categories" data-pm-id="${row.id}">Excluir</button></div></article>`).join('') : '<p class="muted pm-empty">Nenhuma categoria cadastrada.</p>'}</div>
            </div>`);
    }

    function renderPaymentMethods() {
        const rows = moduleState.snapshot?.paymentMethods || [];
        return renderWorkspace('Formas de pagamento', 'Cadastre os meios aceitos e mantenha seus lançamentos padronizados.', `
            <div class="pm-layout">
                <form class="pm-form" data-pm-form="payment_methods">
                    <input type="hidden" name="id">
                    <div class="pm-form-title">Nova forma de pagamento</div>
                    <div class="pm-form-grid"><label>Nome*<input name="name" required placeholder="Ex.: Pix empresa"></label><label>Tipo<select name="methodType"><option value="pix">Pix</option><option value="debito">Débito</option><option value="credito">Crédito</option><option value="dinheiro">Dinheiro</option><option value="boleto">Boleto</option><option value="outro">Outro</option></select></label></div>
                    <div class="pm-form-actions"><button class="primary-btn" type="submit">Salvar forma</button><button class="secondary-btn" type="reset">Limpar</button></div>
                </form>
                <div class="pm-record-list"><div class="pm-list-title">Formas cadastradas <span>${rows.length}</span></div>${rows.length ? rows.map(row => `
                    <article class="pm-record"><div class="pm-record-icon">${icon(row.methodType === 'pix' ? 'pix' : row.methodType === 'credito' ? 'card' : 'debit')}</div><div class="pm-record-main"><strong>${esc(row.name)}</strong><small>${esc(row.methodType)}</small></div><div class="pm-record-actions"><button class="tiny-btn" type="button" data-pm-edit="payment_methods" data-pm-id="${row.id}">Editar</button><button class="tiny-btn" type="button" data-pm-delete="payment_methods" data-pm-id="${row.id}">Excluir</button></div></article>`).join('') : '<p class="muted pm-empty">Nenhuma forma de pagamento cadastrada.</p>'}</div>
            </div>`);
    }

    function renderAccounts() {
        const rows = moduleState.snapshot?.cards || [];
        return renderWorkspace('Contas e cartões', 'Os cartões cadastrados continuam no módulo financeiro e podem ser usados na importação inteligente.', `
            <div class="pm-summary-strip"><div><span>Cartões ativos</span><strong>${rows.length}</strong></div><div><span>Reconhecimento</span><strong>Por final</strong></div><div><span>Importação</span><strong>Automática</strong></div></div>
            <div class="pm-record-list pm-wide-list"><div class="pm-list-title">Cartões cadastrados <span>${rows.length}</span></div>${rows.length ? rows.map(row => `
                <article class="pm-record"><div class="pm-record-icon">${icon('card')}</div><div class="pm-record-main"><strong>${esc(row.nickname)}</strong><small>${esc(row.institution || 'Instituição não informada')} · ${esc(row.brand || 'Bandeira não identificada')} · final ${esc(row.last4 || '----')}</small></div><strong class="pm-record-value">${money(row.limitTotal)}</strong></article>`).join('') : '<p class="muted pm-empty">Nenhum cartão cadastrado.</p>'}</div>
            <div class="pm-inline-actions">${moduleAction('Gerenciar cartões', 'cards', true)}${moduleAction('Importar extrato', 'imports')}</div>`);
    }

    function renderFiscalDocuments() {
        const rows = moduleState.snapshot?.fiscalDocuments || [];
        return renderWorkspace('Documentos fiscais', 'Registre referências de NFS-e, NFC-e e documentos recebidos para conferência.', `
            <div class="pm-layout">
                <form class="pm-form" data-pm-form="tax_documents">
                    <input type="hidden" name="id">
                    <div class="pm-form-title">Novo documento fiscal</div>
                    <div class="pm-form-grid"><label>Tipo<select name="documentType"><option value="nfse">NFS-e</option><option value="nfce">NFC-e</option><option value="nota_entrada">Nota de entrada</option><option value="outro">Outro</option></select></label><label>Número<input name="documentNumber" placeholder="Número ou referência"></label><label>Cliente / fornecedor<input name="customerName" placeholder="Nome da parte"></label><label>Data de emissão<input name="issueDate" type="date" required></label><label>Valor<input name="amount" inputmode="decimal" placeholder="0,00"></label><label>Status<select name="status"><option value="emitido">Emitido</option><option value="rascunho">Rascunho</option><option value="cancelado">Cancelado</option></select></label></div>
                    <label>Observações<textarea name="notes" placeholder="Chave, link, imposto ou observações"></textarea></label>
                    <div class="pm-form-actions"><button class="primary-btn" type="submit">Salvar documento</button><button class="secondary-btn" type="reset">Limpar</button></div>
                </form>
                <div class="pm-record-list"><div class="pm-list-title">Documentos registrados <span>${rows.length}</span></div>${rows.length ? rows.map(row => `
                    <article class="pm-record"><div class="pm-record-icon">${icon('document')}</div><div class="pm-record-main"><strong>${esc(row.documentType.toUpperCase())} ${esc(row.documentNumber || '')}</strong><small>${dateBr(row.issueDate)} · ${esc(row.customerName || 'Parte não informada')} · ${esc(row.status)}</small></div><strong class="pm-record-value">${money(row.amount)}</strong><div class="pm-record-actions"><button class="tiny-btn" type="button" data-pm-edit="tax_documents" data-pm-id="${row.id}">Editar</button><button class="tiny-btn" type="button" data-pm-delete="tax_documents" data-pm-id="${row.id}">Excluir</button></div></article>`).join('') : '<p class="muted pm-empty">Nenhum documento fiscal registrado.</p>'}</div>
            </div>`);
    }

    function scheduleTypeLabel(value) {
        return ({ compromisso: 'Compromisso', vencimento: 'Vencimento', recebimento: 'Recebimento', tarefa: 'Tarefa' })[value] || 'Compromisso';
    }

    function scheduleStatusLabel(value) {
        return ({ agendado: 'Agendado', concluido: 'Concluído', cancelado: 'Cancelado' })[value] || 'Agendado';
    }

    function renderSchedule() {
        const rows = moduleState.snapshot?.schedule || [];
        return renderWorkspace('Agenda operacional', 'Centralize compromissos, vencimentos, recebimentos e tarefas da atividade.', `
            <div class="pm-layout">
                <form class="pm-form" data-pm-form="schedule">
                    <input type="hidden" name="id">
                    <div class="pm-form-title">Novo compromisso</div>
                    <div class="pm-form-grid"><label>Título*<input name="title" required placeholder="Ex.: Receber cliente João"></label><label>Tipo<select name="scheduleType"><option value="compromisso">Compromisso</option><option value="vencimento">Vencimento</option><option value="recebimento">Recebimento</option><option value="tarefa">Tarefa</option></select></label><label>Data*<input name="dueDate" type="date" required></label><label>Hora<input name="dueTime" type="time"></label><label>Valor previsto<input name="amount" inputmode="decimal" placeholder="0,00"></label><label>Cliente / referência<input name="clientName" placeholder="Nome do cliente ou fornecedor"></label><label>Status<select name="status"><option value="agendado">Agendado</option><option value="concluido">Concluído</option><option value="cancelado">Cancelado</option></select></label></div>
                    <label>Observações<textarea name="notes" placeholder="Detalhes, endereço, cobrança ou próxima ação"></textarea></label>
                    <div class="pm-form-actions"><button class="primary-btn" type="submit">Salvar na agenda</button><button class="secondary-btn" type="reset">Limpar</button></div>
                </form>
                <div class="pm-record-list"><div class="pm-list-title">Próximos registros <span>${rows.length}</span></div>${rows.length ? rows.map(row => `
                    <article class="pm-record pm-schedule-record"><div class="pm-record-icon">${icon('agenda')}</div><div class="pm-record-main"><strong>${esc(row.title)}</strong><small>${dateBr(row.dueDate)}${row.dueTime ? ` às ${esc(row.dueTime)}` : ''} · ${scheduleTypeLabel(row.scheduleType)}${row.clientName ? ` · ${esc(row.clientName)}` : ''}</small></div><div class="pm-schedule-meta"><span class="pm-status-badge ${row.status === 'concluido' ? 'is-success' : row.status === 'cancelado' ? 'is-muted' : ''}">${scheduleStatusLabel(row.status)}</span>${Number(row.amount || 0) > 0 ? `<strong class="pm-record-value">${money(row.amount)}</strong>` : ''}</div><div class="pm-record-actions"><button class="tiny-btn" type="button" data-pm-edit="schedule" data-pm-id="${row.id}">Editar</button><button class="tiny-btn" type="button" data-pm-delete="schedule" data-pm-id="${row.id}">Excluir</button></div></article>`).join('') : '<p class="muted pm-empty">Nenhum compromisso registrado.</p>'}</div>
            </div>`);
    }

    function renderSettings() {
        const settings = moduleState.snapshot?.settings || {};
        return renderWorkspace('Dados da atividade', 'Mantenha as informações empresariais que aparecem nos controles e relatórios.', `
            <form class="pm-form pm-single-form" data-pm-form="settings">
                <div class="pm-form-grid"><label>Razão social<input name="legalName" value="${esc(settings.legalName)}" placeholder="Nome jurídico"></label><label>Nome fantasia<input name="tradeName" value="${esc(settings.tradeName)}" placeholder="Nome comercial"></label><label>CNPJ<input name="cnpj" value="${esc(settings.cnpj)}" placeholder="00.000.000/0000-00"></label><label>Telefone<input name="phone" value="${esc(settings.phone)}" placeholder="Contato da atividade"></label><label>Cidade<input name="city" value="${esc(settings.city)}" placeholder="Cidade / UF"></label><label>Dias de aviso<input name="notificationDays" type="number" min="0" max="90" value="${Number(settings.notificationDays ?? 7)}"></label></div>
                <div class="pm-form-actions"><button class="primary-btn" type="submit">Salvar configurações</button></div>
            </form>`);
    }

    function renderPermissions() {
        const permissions = moduleState.snapshot?.permissions || {};
        return renderWorkspace('Controle de acessos', 'Defina permissões padrão para a operação profissional desta conta.', `
            <form class="pm-form pm-single-form" data-pm-form="permissions">
                <div class="pm-form-grid"><label>Perfil de acesso<select name="roleCode"><option value="owner" ${permissions.roleCode === 'owner' ? 'selected' : ''}>Administrador</option><option value="manager" ${permissions.roleCode === 'manager' ? 'selected' : ''}>Gestor</option><option value="operator" ${permissions.roleCode === 'operator' ? 'selected' : ''}>Operador</option></select></label></div>
                <div class="pm-permission-grid"><label><input type="checkbox" name="canViewFinance" ${permissions.canViewFinance !== false ? 'checked' : ''}> Consultar Financeiro</label><label><input type="checkbox" name="canManageClients" ${permissions.canManageClients !== false ? 'checked' : ''}> Gerenciar clientes</label><label><input type="checkbox" name="canManageCatalog" ${permissions.canManageCatalog !== false ? 'checked' : ''}> Gerenciar produtos e serviços</label><label><input type="checkbox" name="canManageFiscal" ${permissions.canManageFiscal !== false ? 'checked' : ''}> Gerenciar fiscal</label></div>
                <div class="pm-form-actions"><button class="primary-btn" type="submit">Salvar permissões</button></div>
            </form>`);
    }

    function renderConsultation() {
        const clients = moduleState.snapshot?.clients || [];
        const history = moduleState.snapshot?.creditChecks || [];
        const providerStatus = moduleState.snapshot?.providerStatus || {};
        return renderWorkspace('Consulta e pré-análise', 'Pesquise nos seus registros e, com autorização, consulte dados cadastrais e de crédito por provedores contratados.', `
            <div class="pm-consultation-grid">
                <section class="pm-form pm-consultation-card"><div class="pm-form-title">Busca nos catálogos</div><form class="pm-search-form" data-pm-search><input name="q" value="${esc(moduleState.search?.query || '')}" placeholder="Nome, categoria, código ou descrição"><button class="primary-btn" type="submit">${icon('search')} Pesquisar</button></form><div class="pm-search-results" id="pmSearchResults">${renderSearchResults(moduleState.search?.results)}</div></section>
                <section class="pm-form pm-consultation-card"><div class="pm-form-title">Pré-análise do cliente <span class="pm-secure-chip">Consulta autorizada</span></div><p class="pm-helper-text">A busca por nome consulta os catálogos internos. Para fontes externas, use CPF/CNPJ: Receita/Serpro identifica CNPJ e Serasa pode consultar CPF ou CNPJ conforme o contrato habilitado.<span class="pm-provider-status-row">${providerStatusChip(providerStatus, 'receita')}${providerStatusChip(providerStatus, 'serasa')}</span></p><form data-pm-credit-form><div class="pm-form-grid"><label>CPF ou CNPJ*<input name="document" required inputmode="numeric" placeholder="Digite somente números"></label><label>Fonte<select name="provider"><option value="combined">Receita/Serpro + Serasa</option><option value="receita">Receita/Serpro · CNPJ</option><option value="serasa">Serasa · CPF/CNPJ</option></select></label><label>Vincular ao cliente<select name="clientId" data-pm-client-select><option value="0">Não vincular agora</option>${clients.map(client => `<option value="${client.id}" data-document="${esc(client.document || '')}">${esc(client.name)}</option>`).join('')}</select></label><label>Finalidade<select name="purpose"><option value="analise_comercial">Análise comercial</option><option value="venda_prazo">Venda a prazo</option><option value="cadastro_cliente">Conferência cadastral</option></select></label></div><label class="pm-consent-label"><input type="checkbox" name="consent" required> Confirmo que tenho autorização ou base legal para consultar este documento e usarei o resultado somente como apoio à pré-análise.</label><div class="pm-form-actions"><button class="primary-btn" type="submit">${icon('search')} Consultar fonte autorizada</button></div></form><div id="pmCreditResult">${renderCreditResult(moduleState.credit)}</div></section>
            </div>
            <section class="pm-record-list pm-credit-history"><div class="pm-list-title">Histórico de consultas <span>${history.length}</span></div>${history.length ? history.map(row => `<article class="pm-record"><div class="pm-record-icon">${icon('key')}</div><div class="pm-record-main"><strong>${esc(row.documentMasked)} · ${creditProviderLabel(row.provider)}</strong><small>${dateTimeBr(row.requestedAt)} · ${creditPurposeLabel(row.purpose)}</small></div><span class="pm-status-badge ${row.status === 'success' ? 'is-success' : row.status === 'partial' ? '' : 'is-muted'}">${creditStatusLabel(row.status)}</span></article>`).join('') : '<p class="muted pm-empty">Nenhuma pré-análise realizada nesta conta.</p>'}</section>`);
    }

    function creditProviderLabel(value) {
        return ({ combined: 'Receita/Serpro + Serasa', receita: 'Receita/Serpro', serasa: 'Serasa', receita_serpro: 'Receita/Serpro' })[value] || 'Provedor';
    }

    function providerStatusChip(statuses, key) {
        const provider = statuses[key] || {};
        return `<span class="pm-provider-status ${provider.configured ? 'is-ready' : 'is-pending'}"><i></i>${esc(provider.label || key)}: ${provider.configured ? 'conectado' : 'aguardando configuração'}</span>`;
    }

    function creditPurposeLabel(value) {
        return ({ analise_comercial: 'Análise comercial', venda_prazo: 'Venda a prazo', cadastro_cliente: 'Conferência cadastral' })[value] || 'Pré-análise';
    }

    function creditStatusLabel(value) {
        return ({ success: 'Concluída', partial: 'Parcial', not_configured: 'Não configurada', error: 'Falha' })[value] || 'Registrada';
    }

    function renderCreditResult(check) {
        if (!check) return '<div class="pm-empty-state">O resultado da consulta aparecerá aqui, com os dados reduzidos ao necessário para a pré-análise.</div>';
        const results = check.results || [];
        return `<div class="pm-credit-result"><div class="pm-credit-result-head"><strong>${creditStatusLabel(check.status)}</strong><small>${esc(check.documentMasked)} · consultado em ${dateTimeBr(check.requestedAt)}</small></div>${results.map(result => {
            const summary = result.summary || {};
            const hasData = result.status === 'success';
            return `<article class="pm-provider-result"><div class="pm-provider-result-title"><span>${creditProviderLabel(result.provider)}</span><b class="pm-status-badge ${result.status === 'success' ? 'is-success' : result.status === 'not_configured' ? 'is-muted' : ''}">${hasData ? 'OK' : creditStatusLabel(result.status)}</b></div>${hasData ? `<div class="pm-credit-facts"><div><small>Nome</small><strong>${esc(summary.name || summary.tradeName || 'Não informado')}</strong></div><div><small>Situação</small><strong>${esc(summary.status || 'Não informado')}</strong></div><div><small>Localidade</small><strong>${esc([summary.city, summary.state].filter(Boolean).join(' / ') || 'Não informado')}</strong></div><div><small>Restrições / protestos</small><strong class="${summary.hasRestrictions ? 'pm-credit-alert' : 'pm-credit-ok'}">${summary.hasRestrictions ? `${Number(summary.restrictionCount || 0) + Number(summary.protestCount || 0)} ocorrência(s)` : 'Nenhuma apontada'}</strong></div>${summary.score !== null && summary.score !== undefined ? `<div><small>Score retornado</small><strong>${esc(summary.score)}</strong></div>` : ''}</div>` : `<p class="pm-provider-message">${esc(result.message || 'Não foi possível obter dados desta fonte.')}</p>`}</article>`;
        }).join('')}<p class="pm-disclaimer">Resultado informativo. A decisão deve considerar documentos, política comercial, consentimento e análise humana.</p></div>`;
    }

    function renderAdministrative() {
        const analytics = moduleState.snapshot?.analytics || {};
        const metrics = [
            ['Receita', analytics.revenue, 'positive'],
            ['Despesas', analytics.expenses, 'negative'],
            ['Resultado após impostos', analytics.operationalResult, Number(analytics.operationalResult || 0) >= 0 ? 'positive' : 'negative'],
            ['Caixa consolidado', analytics.cashBalance, 'neutral'],
            ['Impostos pendentes', analytics.pendingTaxes, analytics.pendingTaxes > 0 ? 'negative' : 'positive'],
            ['Estoque a custo', analytics.stockValue, 'neutral'],
        ];
        const series = analytics.monthlySeries || [];
        const maxSeries = Math.max(...series.flatMap(row => [Number(row.revenue || 0), Number(row.expenses || 0)]), 1);
        const categories = analytics.topCategories || [];
        const maxCategory = Math.max(...categories.map(row => Number(row.amount || 0)), 1);
        return renderWorkspace('Painel de decisão', `Indicadores do mês de ${monthLabel(analytics.month)} com dados de caixa, vendas, despesas, impostos e estoque.`, `
            <div class="pm-analytics-grid">${metrics.map(([label, value, tone]) => `<article class="pm-analytics-card ${tone}"><span>${label}</span><strong>${money(value)}</strong></article>`).join('')}</div>
            <div class="pm-analytics-columns">
                <section class="pm-analytics-panel"><div class="pm-list-title">Evolução anual <span>${esc(String(analytics.month || '').slice(0, 4))}</span></div><div class="pm-series">${series.map(row => `<div class="pm-series-row"><span>${monthLabel(row.month)}</span><div class="pm-series-bars"><i class="pm-bar-revenue" style="height:${Math.max(4, Math.round((Number(row.revenue || 0) / maxSeries) * 100))}%" title="Receita ${money(row.revenue)}"></i><i class="pm-bar-expense" style="height:${Math.max(4, Math.round((Number(row.expenses || 0) / maxSeries) * 100))}%" title="Despesas ${money(row.expenses)}"></i></div></div>`).join('')}</div><div class="pm-legend"><span><i class="pm-bar-revenue"></i> Receita</span><span><i class="pm-bar-expense"></i> Despesas</span></div></section>
                <section class="pm-analytics-panel"><div class="pm-list-title">Maiores despesas <span>${categories.length}</span></div>${categories.length ? `<div class="pm-category-bars">${categories.map(row => `<div class="pm-category-row"><div><strong>${esc(row.name)}</strong><b>${money(row.amount)}</b></div><span><i style="width:${Math.max(5, Math.round((Number(row.amount || 0) / maxCategory) * 100))}%"></i></span></div>`).join('')}</div>` : '<p class="muted pm-empty">Nenhuma despesa categorizada neste período.</p>'}</section>
            </div>
            <div class="pm-summary-strip pm-summary-strip-four"><div><span>Clientes ativos</span><strong>${Number(analytics.clientCount || 0)}</strong></div><div><span>Itens do catálogo</span><strong>${Number(analytics.catalogCount || 0)}</strong></div><div><span>Agenda em aberto</span><strong>${Number(analytics.scheduleOpen || 0)}</strong></div><div><span>Valor de venda em estoque</span><strong>${money(analytics.catalogValue)}</strong></div></div>`);
    }

    function renderSearchResults(results) {
        if (!results) return '<div class="pm-empty-state">Digite uma busca para consultar seus registros.</div>';
        const clients = results.clients || [];
        const catalog = results.catalog || [];
        const transactions = results.transactions || [];
        const total = clients.length + catalog.length + transactions.length;
        if (!total) return '<div class="pm-empty-state">Nenhum resultado encontrado.</div>';
        return `<div class="pm-search-count">${total} resultado(s) encontrado(s)</div>${[
            ...clients.map(row => `<article class="pm-search-row">${icon('users')}<div><strong>${esc(row.name)}</strong><small>Cliente · ${esc(row.document || 'sem documento')}</small></div></article>`),
            ...catalog.map(row => `<article class="pm-search-row">${icon('box')}<div><strong>${esc(row.name)}</strong><small>${row.itemType === 'service' ? 'Serviço' : 'Produto'} · ${money(row.price)}</small></div></article>`),
            ...transactions.map(row => `<article class="pm-search-row">${icon('movements')}<div><strong>${esc(row.description)}</strong><small>${dateBr(row.movementDate)} · ${esc(row.category || 'Sem categoria')}</small></div><b>${money(row.amount)}</b></article>`),
        ].join('')}`;
    }

    function renderCatalogCenter() {
        const snapshot = moduleState.snapshot || {};
        const cards = [
            ['clients', 'Clientes', 'Base comercial, contatos e documentos.', 'users', (snapshot.clients || []).length],
            ['products', 'Produtos e serviços', 'Itens vendidos, serviços, preço e estoque.', 'box', (snapshot.catalog || []).length],
            ['cash', 'Caixas e contas', 'Saldos separados por origem da operação.', 'wallet', (snapshot.cash || []).length],
            ['categories', 'Categorias', 'Classificação de receitas e despesas.', 'cat-other', (snapshot.categories || []).length],
            ['payment_methods', 'Formas de pagamento', 'Pix, débito, crédito, dinheiro e boleto.', 'debit', (snapshot.paymentMethods || []).length],
            ['tax-documents', 'Documentos fiscais', 'NFS-e, NFC-e e notas recebidas.', 'document', (snapshot.fiscalDocuments || []).length],
        ];
        return renderWorkspace('Catálogos integrados', 'Todos os catálogos desta operação ficam conectados aos lançamentos, compras, recebimentos e indicadores.', `
            <div class="pm-catalog-hub">${cards.map(([target, title, description, iconName, count]) => `<article class="pm-catalog-card"><span class="pm-catalog-icon">${icon(iconName)}</span><div class="pm-catalog-content"><div class="pm-catalog-card-top"><h3>${title}</h3><span>${count}</span></div><p>${description}</p><button class="primary-btn pm-action" type="button" data-pm-target="business:${esc(target)}">Abrir catálogo</button></div></article>`).join('')}</div>
            <div class="pm-catalog-bridge"><div><span class="eyebrow">Fluxo conectado</span><strong>Use os cadastros para alimentar a rotina</strong><p>Ao abrir lançamentos, compras ou recebimentos, os registros desta central continuam disponíveis para consulta e organização.</p></div><div class="pm-inline-actions">${moduleAction('Abrir agenda', 'business:schedule', true)}${moduleAction('Abrir lançamentos', 'movements')}${moduleAction('Ver indicadores', 'business:administrative')}</div></div>`);
    }

    function renderAccountsOrOperations(name) {
        const links = {
            schedule: [['Agenda financeira', 'Acompanhe competência, recebimentos e próximos vencimentos.', 'Ver Financeiro', 'dashboard'], ['Planejamento', 'Use o calendário para organizar as prioridades da operação.', 'Abrir calendário', 'dashboard']],
            transactions: [['Novo lançamento', 'Cadastre movimentações com categoria, forma de pagamento e parcelas.', 'Adicionar lançamento', 'movements'], ['Movimentos recentes', 'Edite ou exclua registros quando necessário.', 'Consultar movimentos', 'movements']],
            purchases: [['Compras da competência', 'Veja compras, fornecedores e o impacto de cada gasto no caixa.', 'Ver movimentos', 'movements'], ['Compras no cartão', 'Acompanhe parcelas e comprometimento do limite.', 'Ver cartões', 'cards']],
            registrations: [['Clientes', 'Cadastre clientes, documentos e contatos comerciais.', 'Abrir clientes', 'business:clients'], ['Produtos e serviços', 'Mantenha seu catálogo, preço, custo e estoque.', 'Abrir catálogo', 'business:products']],
            help: [['Importação inteligente', 'Use comprovantes PNG, JPG ou PDF para identificar os lançamentos.', 'Abrir importação', 'imports'], ['Primeiros passos', 'Cadastre cartões, caixas, clientes e movimentos da atividade.', 'Ver Financeiro', 'dashboard']],
            administrative: [['Indicadores da operação', 'Acompanhe receita, despesas, resultado, patrimônio e obrigações.', 'Abrir profissional', 'professional'], ['Resumo para decisão', 'Emita o resumo da competência em PDF.', 'Ver Financeiro', 'dashboard']],
        }[name] || [['Área profissional', 'Este módulo está conectado à estrutura profissional da conta.', 'Abrir Financeiro', 'dashboard']];
        return renderWorkspace('Ações rápidas', 'Escolha uma ação para continuar sua rotina.', `<div class="pm-shortcut-grid">${links.map(([title, text, label, target]) => `<article class="pm-shortcut-card"><span class="pm-shortcut-icon">${icon(modules[name]?.icon || 'briefcase')}</span><div><h3>${esc(title)}</h3><p>${esc(text)}</p></div>${moduleAction(label, target, true)}</article>`).join('')}</div>`);
    }

    function renderModuleBody(name) {
        if (name === 'schedule') return renderSchedule();
        if (name === 'registrations') return renderCatalogCenter();
        if (name === 'clients') return renderClients();
        if (name === 'products') return renderProducts();
        if (name === 'cash' || name === 'wallets') return renderCashModule();
        if (name === 'categories') return renderCategories();
        if (name === 'payment_methods') return renderPaymentMethods();
        if (name === 'accounts') return renderAccounts();
        if (name === 'tax-documents') return renderFiscalDocuments();
        if (name === 'settings') return renderSettings();
        if (name === 'permissions') return renderPermissions();
        if (name === 'consultation') return renderConsultation();
        if (name === 'administrative') return renderAdministrative();
        return renderAccountsOrOperations(name);
    }

    function renderCurrentModule() {
        if (!moduleState.current || !modules[moduleState.current]) return;
        setModuleHeader(moduleState.current);
        el('businessModuleGrid').innerHTML = renderModuleBody(moduleState.current);
        el('businessModuleStatus').textContent = moduleState.loading ? 'Sincronizando dados do módulo...' : 'Módulo profissional conectado aos seus dados.';
    }

    async function openProfessionalModule(name) {
        const resolvedName = name === 'payment-methods' ? 'payment_methods' : name;
        if (!modules[resolvedName]) return false;
        document.querySelectorAll('.professional-nav-item').forEach(item => item.classList.toggle('is-active', item.dataset.businessModule === name));
        if (resolvedName === 'professional') {
            window.activateTab('professional');
            return true;
        }
        moduleState.current = resolvedName;
        el('panel-business-module').hidden = false;
        renderCurrentModule();
        window.activateTab('business-module');
        try {
            await loadSnapshot();
            renderCurrentModule();
        } catch (error) {
            el('businessModuleStatus').textContent = error.message || 'Não foi possível carregar este módulo.';
        }
        return true;
    }

    function formRecord(form) {
        const record = Object.fromEntries(new FormData(form).entries());
        form.querySelectorAll('input[type="checkbox"]').forEach(input => { record[input.name] = input.checked; });
        return record;
    }

    function loadRecordIntoForm(entity, id) {
        const key = snapshotKey[entity];
        const record = (moduleState.snapshot?.[key] || []).find(item => Number(item.id) === Number(id));
        const form = document.querySelector(`[data-pm-form="${entity}"]`);
        if (!record || !form) return;
        Object.entries(record).forEach(([name, value]) => {
            const input = form.elements[name];
            if (!input) return;
            if (input.type === 'checkbox') input.checked = Boolean(value);
            else input.value = value ?? '';
        });
        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    async function submitModuleForm(form) {
        const entity = form.dataset.pmForm;
        const record = formRecord(form);
        try {
            form.classList.add('is-loading');
            const response = await request('save', entity, record);
            moduleState.snapshot = response.snapshot;
            renderCurrentModule();
        } catch (error) {
            el('businessModuleStatus').textContent = error.message || `Não foi possível salvar ${entityLabels[entity] || 'o registro'}.`;
        } finally {
            form.classList.remove('is-loading');
        }
    }

    async function deleteModuleRecord(entity, id) {
        if (!confirm(`Excluir este ${entityLabels[entity] || 'registro'}?`)) return;
        try {
            const response = await request('delete', entity, null, Number(id));
            moduleState.snapshot = response.snapshot;
            renderCurrentModule();
        } catch (error) {
            el('businessModuleStatus').textContent = error.message || 'Não foi possível excluir o registro.';
        }
    }

    async function searchModule(form) {
        const query = new FormData(form).get('q') || '';
        try {
            const response = await window.api(`api/professional.php?action=search&q=${encodeURIComponent(query)}`);
            moduleState.search = { query, results: response.results };
            renderCurrentModule();
        } catch (error) {
            el('businessModuleStatus').textContent = error.message || 'Não foi possível realizar a consulta.';
        }
    }

    async function submitCreditForm(form) {
        const data = new FormData(form);
        const payload = {
            action: 'credit_precheck',
            document: data.get('document') || '',
            provider: data.get('provider') || 'combined',
            clientId: Number(data.get('clientId') || 0),
            purpose: data.get('purpose') || 'analise_comercial',
            consent: data.get('consent') === 'on',
        };
        try {
            form.classList.add('is-loading');
        const response = await window.api('api/professional.php', { method: 'POST', body: JSON.stringify(payload) });
            moduleState.credit = response.check;
            if (moduleState.snapshot) moduleState.snapshot.creditChecks = response.history || moduleState.snapshot.creditChecks || [];
            renderCurrentModule();
        } catch (error) {
            el('businessModuleStatus').textContent = error.message || 'Não foi possível concluir a pré-análise.';
        } finally {
            form.classList.remove('is-loading');
        }
    }

    document.addEventListener('submit', event => {
        const form = event.target.closest('[data-pm-form]');
        if (form) {
            event.preventDefault();
            submitModuleForm(form);
            return;
        }
        const searchForm = event.target.closest('[data-pm-search]');
        if (searchForm) {
            event.preventDefault();
            searchModule(searchForm);
            return;
        }
        const creditForm = event.target.closest('[data-pm-credit-form]');
        if (creditForm) {
            event.preventDefault();
            submitCreditForm(creditForm);
        }
    });

    document.addEventListener('click', event => {
        const target = event.target.closest('[data-pm-target]');
        if (target) {
            const destination = target.dataset.pmTarget || '';
            if (destination.startsWith('business:')) window.activateBusinessModule(destination.slice('business:'.length));
            else window.activateTab(destination);
            return;
        }
        const edit = event.target.closest('[data-pm-edit]');
        if (edit) {
            loadRecordIntoForm(edit.dataset.pmEdit, edit.dataset.pmId);
            return;
        }
        const remove = event.target.closest('[data-pm-delete]');
        if (remove) deleteModuleRecord(remove.dataset.pmDelete, remove.dataset.pmId);
    });

    document.addEventListener('change', event => {
        const clientSelect = event.target.closest('[data-pm-client-select]');
        if (!clientSelect) return;
        const documentInput = clientSelect.form?.elements?.document;
        const documentValue = clientSelect.selectedOptions?.[0]?.dataset?.document || '';
        if (documentInput && documentValue) documentInput.value = documentValue;
    });

    window.openProfessionalModule = openProfessionalModule;
})();
