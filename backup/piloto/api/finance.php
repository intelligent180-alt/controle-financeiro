<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function sf_transaction_payload(array $row): array
{
    return [
        'id' => (int)$row['id'],
        'type' => (string)$row['type'],
        'description' => (string)$row['description'],
        'category' => (string)($row['category'] ?? ''),
        'amount' => (float)$row['amount'],
        'installmentCount' => max(1, (int)($row['installment_count'] ?? 1)),
        'installmentNumber' => max(1, (int)($row['installment_number'] ?? 1)),
        'installmentAmount' => (float)(($row['installment_amount'] ?? 0) ?: $row['amount']),
        'installmentGroup' => (string)($row['installment_group'] ?? ''),
        'movementDate' => (string)$row['movement_date'],
        'movementTime' => (string)($row['movement_time'] ?? ''),
        'paymentMethod' => (string)($row['payment_method'] ?? 'pix'),
        'periodType' => (string)$row['period_type'],
        'referenceMonth' => (string)$row['reference_month'],
        'periodDueDate' => (string)($row['period_due_date'] ?? ''),
        'cardId' => $row['card_id'] ? (int)$row['card_id'] : null,
        'cardNickname' => (string)($row['card_nickname'] ?? ''),
        'cardLast4' => (string)($row['card_last4'] ?? ''),
        'source' => (string)($row['source'] ?? 'manual'),
        'status' => (string)($row['status'] ?? 'pago'),
        'notes' => (string)($row['notes'] ?? ''),
        'importId' => $row['import_id'] ? (int)$row['import_id'] : null,
        'createdAt' => (string)$row['created_at'],
        'updatedAt' => (string)$row['updated_at'],
    ];
}

function sf_income_payload(array $row): array
{
    $payload = json_decode((string)($row['payload_json'] ?? '{}'), true) ?: [];
    return [
        'id' => (int)$row['id'],
        'referenceMonth' => (string)$row['reference_month'],
        'periodType' => (string)$row['period_type'],
        'paymentDate' => (string)$row['payment_date'],
        'grossSalary' => (float)$row['gross_salary'],
        'benefits' => (float)$row['benefits'],
        'nightShift' => (float)$row['night_shift'],
        'hazardPay' => (float)$row['hazard_pay'],
        'extraIncome' => (float)$row['extra_income'],
        'inssDiscount' => (float)$row['inss_discount'],
        'transportDiscount' => (float)$row['transport_discount'],
        'otherDiscounts' => (float)$row['other_discounts'],
        'netAmount' => (float)$row['net_amount'],
        'payload' => $payload,
    ];
}

function sf_card_payload(array $row): array
{
    return [
        'id' => (int)$row['id'],
        'nickname' => (string)$row['nickname'],
        'institution' => (string)($row['institution'] ?? ''),
        'brand' => (string)($row['brand'] ?? ''),
        'bin4' => (string)($row['bin4'] ?? ''),
        'last4' => (string)($row['last4'] ?? ''),
        'productName' => (string)($row['product_name'] ?? ''),
        'cardholderName' => (string)($row['cardholder_name'] ?? ''),
        'expiryDate' => (string)($row['expiry_date'] ?? ''),
        'closingDay' => (int)$row['closing_day'],
        'dueDay' => (int)$row['due_day'],
        'limitTotal' => (float)$row['limit_total'],
        'active' => (bool)$row['active'],
        'notes' => (string)($row['notes'] ?? ''),
    ];
}

function sf_card_brand_from_bin4(string $bin4): string
{
    $bin4 = substr(preg_replace('/\D+/', '', $bin4), 0, 4);
    if (strlen($bin4) < 4) return '';
    $prefix = (int)$bin4;

    // Alguns prefixos Elo conhecidos precisam ser avaliados antes das faixas
    // amplas de Visa, Discover e UnionPay.
    if (in_array($bin4, ['4389', '4573', '4576', '5041', '6277', '6362', '6363'], true)
        || ($prefix >= 6500 && $prefix <= 6509)
        || $prefix === 6516
        || ($prefix >= 6550 && $prefix <= 6559)) {
        return 'Elo';
    }
    if ($bin4 === '6062' || $bin4 === '3841') return 'Hipercard';
    if (str_starts_with($bin4, '34') || str_starts_with($bin4, '37')) return 'American Express';
    if (($prefix >= 5100 && $prefix <= 5599) || ($prefix >= 2221 && $prefix <= 2720)) return 'Mastercard';
    if ($prefix >= 3528 && $prefix <= 3589) return 'JCB';
    if ($bin4 === '6011' || ($prefix >= 6440 && $prefix <= 6499)) return 'Discover';
    if (str_starts_with($bin4, '4')) return 'Visa';
    if ($prefix >= 6200 && $prefix <= 6299) return 'UnionPay';
    if (($prefix >= 3000 && $prefix <= 3059) || ($prefix >= 3600 && $prefix <= 3699)) return 'Diners Club';
    return '';
}

function sf_card_expiry(string $value): string
{
    $value = trim($value);
    if (preg_match('/^(0[1-9]|1[0-2])\s*[\/\-]\s*(\d{2}|\d{4})$/', $value, $match)) {
        return $match[1] . '/' . substr($match[2], -2);
    }
    $digits = preg_replace('/\D+/', '', $value);
    if (strlen($digits) === 4 && (int)substr($digits, 0, 2) >= 1 && (int)substr($digits, 0, 2) <= 12) {
        return substr($digits, 0, 2) . '/' . substr($digits, 2, 2);
    }
    if (strlen($digits) === 6 && (int)substr($digits, 0, 2) >= 1 && (int)substr($digits, 0, 2) <= 12) {
        return substr($digits, 0, 2) . '/' . substr($digits, -2);
    }
    return '';
}

function sf_goal_payload(array $row): array
{
    return [
        'id' => (int)$row['id'],
        'name' => (string)$row['name'],
        'targetAmount' => (float)$row['target_amount'],
        'savedAmount' => (float)$row['saved_amount'],
        'referenceMonth' => (string)$row['reference_month'],
        'active' => (bool)$row['active'],
    ];
}

function sf_list_transactions(PDO $pdo, int $userId, string $month): array
{
    $stmt = $pdo->prepare("
        SELECT t.*, c.nickname AS card_nickname, c.last4 AS card_last4
          FROM transactions t
          LEFT JOIN cards c ON c.id = t.card_id AND c.user_id = t.user_id
         WHERE t.user_id = :user_id
           AND t.reference_month = :reference_month
         ORDER BY t.movement_date DESC, t.id DESC
    ");
    $stmt->execute([':user_id' => $userId, ':reference_month' => $month]);
    return array_map('sf_transaction_payload', $stmt->fetchAll());
}

function sf_list_incomes(PDO $pdo, int $userId, string $month): array
{
    $stmt = $pdo->prepare("
        SELECT *
          FROM incomes
         WHERE user_id = :user_id
           AND reference_month = :reference_month
         ORDER BY CASE period_type WHEN 'dia20' THEN 1 ELSE 2 END
    ");
    $stmt->execute([':user_id' => $userId, ':reference_month' => $month]);
    return array_map('sf_income_payload', $stmt->fetchAll());
}

function sf_list_cards(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT *
          FROM cards
         WHERE user_id = :user_id
           AND active = 1
         ORDER BY nickname ASC
    ");
    $stmt->execute([':user_id' => $userId]);
    return array_map('sf_card_payload', $stmt->fetchAll());
}

function sf_list_goals(PDO $pdo, int $userId, string $month): array
{
    $stmt = $pdo->prepare("
        SELECT *
          FROM goals
         WHERE user_id = :user_id
           AND reference_month = :reference_month
           AND active = 1
         ORDER BY id DESC
    ");
    $stmt->execute([':user_id' => $userId, ':reference_month' => $month]);
    return array_map('sf_goal_payload', $stmt->fetchAll());
}

function sf_financial_insights(float $income, float $expenses, float $pending, float $saved, float $balance): array
{
    if ($income <= 0) {
        return [
            'Registre o adiantamento do dia 20 e o pagamento do 5o dia util para liberar a leitura real do mes.',
            'Comece pelas contas fixas e fatura de cartao. Depois registre gastos variaveis para saber quanto sobra.',
        ];
    }

    $usage = $expenses / max($income, 1);
    $items = [];
    if ($usage >= 0.85) {
        $items[] = 'Alerta: voce ja comprometeu mais de 85% da renda liquida do periodo. Segure compras nao essenciais.';
    } elseif ($usage >= 0.65) {
        $items[] = 'Zona de atencao: revise parcelas, delivery, mercado e gastos pequenos recorrentes.';
    } else {
        $items[] = 'O mes esta respirando melhor. Se o saldo for positivo, separe a reserva antes de gastar.';
    }

    if ($pending > 0) {
        $items[] = 'Priorize pendencias com juros: cartao, cheque especial, boleto atrasado e renegociacoes.';
    }

    if ($balance > 0 && $saved <= 0) {
        $suggestion = round(max(10, min($balance * 0.2, $income * 0.1)), 2);
        $items[] = 'Meta inicial sugerida: guardar R$ ' . number_format($suggestion, 2, ',', '.') . ' neste mes.';
    }

    if ($balance < 0) {
        $items[] = 'Saldo projetado negativo. Separe obrigatorios, pause variaveis e redistribua para dia 20 ou 5o dia util.';
    }

    return $items;
}

function sf_dashboard(PDO $pdo, array $user, string $month): array
{
    $userId = (int)$user['id'];
    $transactions = sf_list_transactions($pdo, $userId, $month);
    $incomes = sf_list_incomes($pdo, $userId, $month);
    $cards = sf_list_cards($pdo, $userId);
    $goals = sf_list_goals($pdo, $userId, $month);

    $incomeSalary = 0.0;
    $incomeOther = 0.0;
    $expenses = 0.0;
    $pending = 0.0;
    $saved = 0.0;
    $categories = [];
    $periods = [
        'dia20' => ['income' => 0.0, 'expenses' => 0.0, 'pending' => 0.0],
        'quinto_util' => ['income' => 0.0, 'expenses' => 0.0, 'pending' => 0.0],
    ];
    $cardSummary = [];

    foreach ($cards as $card) {
        $cardSummary[(int)$card['id']] = [
            'id' => (int)$card['id'],
            'nickname' => $card['nickname'],
            'last4' => $card['last4'],
            'limitTotal' => (float)$card['limitTotal'],
            'used' => 0.0,
            'available' => (float)$card['limitTotal'],
        ];
    }

    foreach ($incomes as $income) {
        $incomeSalary += (float)$income['netAmount'];
        $period = $income['periodType'];
        if (isset($periods[$period])) {
            $periods[$period]['income'] += (float)$income['netAmount'];
        }
    }

    foreach ($goals as $goal) {
        $saved += (float)$goal['savedAmount'];
    }

    foreach ($transactions as $transaction) {
        $amount = (float)$transaction['amount'];
        $period = $transaction['periodType'];
        $type = $transaction['type'];

        if ($type === 'entrada') {
            $incomeOther += $amount;
            if (isset($periods[$period])) {
                $periods[$period]['income'] += $amount;
            }
            continue;
        }

        $expenses += $amount;
        if (isset($periods[$period])) {
            $periods[$period]['expenses'] += $amount;
        }
        if (($transaction['status'] ?? '') !== 'pago') {
            $pending += $amount;
            if (isset($periods[$period])) {
                $periods[$period]['pending'] += $amount;
            }
        }

        $category = $transaction['category'] ?: 'Outros';
        $categories[$category] = ($categories[$category] ?? 0) + $amount;

        $cardId = (int)($transaction['cardId'] ?? 0);
        if ($cardId && isset($cardSummary[$cardId])) {
            $cardSummary[$cardId]['used'] += $amount;
            $cardSummary[$cardId]['available'] = max(0, $cardSummary[$cardId]['limitTotal'] - $cardSummary[$cardId]['used']);
        }
    }

    uasort($categories, fn($a, $b) => $b <=> $a);
    $incomeTotal = $incomeSalary + $incomeOther;
    $balance = $incomeTotal - $expenses - $saved;
    $cardLimitTotal = array_sum(array_column($cardSummary, 'limitTotal'));
    $cardUsedTotal = array_sum(array_column($cardSummary, 'used'));
    $cardAvailableTotal = array_sum(array_column($cardSummary, 'available'));

    return [
        'ok' => true,
        'month' => $month,
        'user' => sf_user_payload($user),
        'calendar' => sf_month_periods($month),
        'summary' => [
            'income' => round($incomeTotal, 2),
            'incomeSalary' => round($incomeSalary, 2),
            'incomeOther' => round($incomeOther, 2),
            'expenses' => round($expenses, 2),
            'pending' => round($pending, 2),
            'savedGoals' => round($saved, 2),
            'availableBalance' => round($balance, 2),
            'usagePercent' => $incomeTotal > 0 ? round(($expenses / $incomeTotal) * 100, 1) : 0,
            'savingPercent' => $incomeTotal > 0 ? round(($saved / $incomeTotal) * 100, 1) : 0,
            'cardLimitTotal' => round((float)$cardLimitTotal, 2),
            'cardUsedTotal' => round((float)$cardUsedTotal, 2),
            'cardAvailableTotal' => round((float)$cardAvailableTotal, 2),
            'cardUsagePercent' => $cardLimitTotal > 0 ? round(($cardUsedTotal / $cardLimitTotal) * 100, 1) : 0,
        ],
        'periods' => $periods,
        'categories' => array_map(
            fn($name, $amount) => ['name' => $name, 'amount' => round((float)$amount, 2)],
            array_keys($categories),
            array_values($categories)
        ),
        'cardSummary' => array_values($cardSummary),
        'transactions' => $transactions,
        'incomes' => $incomes,
        'cards' => $cards,
        'goals' => $goals,
        'insights' => sf_financial_insights($incomeTotal, $expenses, $pending, $saved, $balance),
        'taxRules' => [
            'inssYear' => 2026,
            'source' => 'Gov.br INSS - tabela de contribuicao mensal atualizada em 13/01/2026',
        ],
    ];
}

function sf_card_belongs(PDO $pdo, int $cardId, int $userId): bool
{
    if ($cardId <= 0) {
        return false;
    }
    $stmt = $pdo->prepare('SELECT id FROM cards WHERE id = :id AND user_id = :user_id AND active = 1 LIMIT 1');
    $stmt->execute([':id' => $cardId, ':user_id' => $userId]);
    return (bool)$stmt->fetchColumn();
}

function sf_save_transaction(PDO $pdo, int $userId, array $data, ?int $importId = null): int
{
    $id = (int)($data['id'] ?? 0);
    $referenceMonth = sf_month($data['referenceMonth'] ?? null);
    $periodType = sf_period($data['periodType'] ?? null);
    $cardId = (int)($data['cardId'] ?? 0);
    $amount = abs(sf_money($data['amount'] ?? 0));
    $description = sf_text($data['description'] ?? '', 255);
    $type = sf_text($data['type'] ?? 'saida', 40);
    $allowedTypes = ['entrada', 'saida', 'cartao_credito', 'fatura_cartao', 'reserva', 'ajuste'];

    if ($description === '' || $amount <= 0) {
        sf_json(['ok' => false, 'message' => 'Informe descricao e valor.'], 422);
    }
    if ($cardId > 0 && !sf_card_belongs($pdo, $cardId, $userId)) {
        sf_json(['ok' => false, 'message' => 'Cartao nao encontrado para este usuario.'], 404);
    }

    $params = [
        ':user_id' => $userId,
        ':type' => in_array($type, $allowedTypes, true) ? $type : 'saida',
        ':description' => $description,
        ':category' => sf_text($data['category'] ?? '', 120),
        ':amount' => $amount,
        ':installment_count' => max(1, min(60, (int)($data['installmentCount'] ?? 1))),
        ':installment_number' => max(1, min(60, (int)($data['installmentNumber'] ?? 1))),
        ':installment_amount' => $amount,
        ':installment_group' => sf_text($data['installmentGroup'] ?? '', 80),
        ':movement_date' => sf_date($data['movementDate'] ?? null),
        ':movement_time' => sf_time($data['movementTime'] ?? ''),
        ':payment_method' => sf_text($data['paymentMethod'] ?? 'pix', 60),
        ':period_type' => $periodType,
        ':reference_month' => $referenceMonth,
        ':period_due_date' => sf_period_due_date($referenceMonth, $periodType),
        ':card_id' => $cardId > 0 ? $cardId : null,
        ':source' => sf_text($data['source'] ?? 'manual', 60),
        ':status' => sf_text($data['status'] ?? 'pago', 40),
        ':notes' => sf_text($data['notes'] ?? '', 800),
        ':import_id' => $importId,
        ':updated_at' => sf_now(),
    ];

    if ($id > 0) {
        $params[':id'] = $id;
        $stmt = $pdo->prepare("
            UPDATE transactions
               SET type = :type,
                   description = :description,
                   category = :category,
                   amount = :amount,
                   installment_count = :installment_count,
                   installment_number = :installment_number,
                   installment_amount = :installment_amount,
                   installment_group = :installment_group,
                   movement_date = :movement_date,
                   movement_time = :movement_time,
                   payment_method = :payment_method,
                   period_type = :period_type,
                   reference_month = :reference_month,
                   period_due_date = :period_due_date,
                   card_id = :card_id,
                   source = :source,
                   status = :status,
                   notes = :notes,
                   import_id = COALESCE(:import_id, import_id),
                   updated_at = :updated_at
             WHERE id = :id
               AND user_id = :user_id
        ");
        $stmt->execute($params);
        return $id;
    }

    $params[':created_at'] = $params[':updated_at'];
    $stmt = $pdo->prepare("
        INSERT INTO transactions (
            user_id, type, description, category, amount, movement_date, payment_method,
            installment_count, installment_number, installment_amount, installment_group,
            movement_time, period_type, reference_month, period_due_date, card_id, source, status, notes,
            import_id, created_at, updated_at
        ) VALUES (
            :user_id, :type, :description, :category, :amount, :movement_date, :payment_method,
            :installment_count, :installment_number, :installment_amount, :installment_group,
            :movement_time, :period_type, :reference_month, :period_due_date, :card_id, :source, :status, :notes,
            :import_id, :created_at, :updated_at
        )
    ");
    $stmt->execute($params);
    return (int)$pdo->lastInsertId();
}

function sf_is_card_purchase(array $data): bool
{
    $type = sf_text($data['type'] ?? '', 40);
    $paymentMethod = sf_text($data['paymentMethod'] ?? '', 60);
    if ($type === 'fatura_cartao') {
        return false;
    }
    return $type === 'cartao_credito' || $paymentMethod === 'credito' || (int)($data['cardId'] ?? 0) > 0;
}

function sf_shift_month(string $month, int $offset): string
{
    $base = new DateTimeImmutable(sf_month($month) . '-01');
    return $base->modify(($offset >= 0 ? '+' : '') . $offset . ' months')->format('Y-m');
}

function sf_shift_date(string $date, int $offset): string
{
    $source = new DateTimeImmutable(sf_date($date));
    $day = (int)$source->format('d');
    $target = new DateTimeImmutable($source->format('Y-m-01'));
    if ($offset !== 0) {
        $target = $target->modify(($offset >= 0 ? '+' : '') . $offset . ' months');
    }
    $day = min($day, (int)$target->format('t'));
    return $target->setDate((int)$target->format('Y'), (int)$target->format('m'), $day)->format('Y-m-d');
}

function sf_credit_purchase_after_cutoff(string $date): bool
{
    $purchaseDate = sf_date($date);
    [$year, $month] = array_map('intval', explode('-', substr($purchaseDate, 0, 7)));
    return $purchaseDate > sf_fifth_business_day($year, $month);
}

function sf_apply_credit_schedule(array $data): array
{
    if (!empty($data['creditScheduleApplied'])
        || (int)($data['id'] ?? 0) > 0
        || !sf_is_card_purchase($data)) {
        return $data;
    }

    $purchaseDate = sf_date($data['movementDate'] ?? null);
    $purchaseMonth = substr($purchaseDate, 0, 7);
    $data['movementDate'] = $purchaseDate;

    if (sf_credit_purchase_after_cutoff($purchaseDate)) {
        $data['movementDate'] = sf_shift_date($purchaseDate, 1);
        $data['referenceMonth'] = sf_shift_month($purchaseMonth, 1);
        $data['creditScheduledFromPurchase'] = true;
    } else {
        $data['referenceMonth'] = $purchaseMonth;
        $data['creditScheduledFromPurchase'] = false;
    }
    $data['creditScheduleApplied'] = true;

    return $data;
}

function sf_save_transaction_installments(PDO $pdo, int $userId, array $data, bool $expand, ?int $importId = null): array
{
    $data = sf_apply_credit_schedule($data);
    $count = max(1, min(60, (int)($data['installmentCount'] ?? 1)));
    $id = (int)($data['id'] ?? 0);
    if (!$expand || $id > 0 || $count <= 1 || !sf_is_card_purchase($data)) {
        return [sf_save_transaction($pdo, $userId, $data, $importId)];
    }

    $total = abs(sf_money($data['amount'] ?? 0));
    $totalCents = (int)round($total * 100);
    $baseCents = intdiv($totalCents, $count);
    $remainder = $totalCents - ($baseCents * $count);
    $group = 'parcelas-' . bin2hex(random_bytes(8));
    $referenceMonth = sf_month($data['referenceMonth'] ?? null);
    $movementDate = sf_date($data['movementDate'] ?? null);
    $ids = [];

    for ($number = 1; $number <= $count; $number++) {
        $installmentCents = $baseCents + ($number === $count ? $remainder : 0);
        $child = $data;
        $child['id'] = 0;
        $child['amount'] = $installmentCents / 100;
        $child['installmentCount'] = $count;
        $child['installmentNumber'] = $number;
        $child['installmentAmount'] = $child['amount'];
        $child['installmentGroup'] = $group;
        $child['referenceMonth'] = sf_shift_month($referenceMonth, $number - 1);
        $child['movementDate'] = sf_shift_date($movementDate, $number - 1);
        $child['description'] = sf_text(($data['description'] ?? 'Compra no cartao') . " ({$number}/{$count})", 255);
        $ids[] = sf_save_transaction($pdo, $userId, $child, $importId);
    }

    return $ids;
}

function sf_category_guess(string $text): string
{
    $base = sf_search_text($text);
    $map = [
        'Pensao' => ['pensao'],
        'Mercado' => ['mercado', 'supermercado', 'atacadao', 'assai', 'carrefour'],
        'Transporte' => ['uber', '99', 'onibus', 'metro', 'combustivel', 'posto', 'gasolina'],
        'Saude' => ['farmacia', 'drogaria', 'consulta', 'hospital', 'exame'],
        'Alimentacao' => ['ifood', 'restaurante', 'lanchonete', 'pizza', 'hamburguer', 'padaria', 'cafeteria'],
        'Moradia' => ['aluguel', 'condominio', 'energia', 'cemig', 'agua', 'internet'],
        'Vestuario' => ['roupa', 'vestuario', 'calcado', 'sapato', 'tenis', 'renner', 'riachuelo', 'zara'],
        'Eletronicos' => ['celular', 'smartphone', 'computador', 'notebook', 'eletronico', 'fone', 'tablet', 'informatica'],
        'Educacao' => ['escola', 'curso', 'faculdade', 'universidade', 'livro', 'educacao'],
        'Lazer' => ['cinema', 'teatro', 'viagem', 'hotel', 'jogo', 'parque', 'show'],
        'Assinaturas' => ['netflix', 'spotify', 'amazon prime', 'prime video', 'youtube premium', 'assinatura'],
        'Pets' => ['pet', 'veterinario', 'racao', 'petshop', 'animal'],
        'Beleza' => ['salao', 'barbeiro', 'cabeleireiro', 'cosmetico', 'manicure', 'estetica'],
        'Cartao' => ['fatura', 'cartao'],
        'Receita' => ['salario', 'adiantamento', 'pagamento', 'deposito', 'pix recebido'],
    ];
    foreach ($map as $category => $terms) {
        foreach ($terms as $term) {
            if (str_contains($base, $term)) {
                return $category;
            }
        }
    }
    return 'Outros';
}

function sf_payment_guess(string $text): string
{
    $base = sf_search_text($text);
    if (str_contains($base, 'pix')) return 'pix';
    if (str_contains($base, 'debito')) return 'debito';
    if (str_contains($base, 'cartao') || str_contains($base, 'credito') || str_contains($base, 'mastercard') || str_contains($base, 'visa') || str_contains($base, 'elo') || str_contains($base, 'amex')) return 'credito';
    if (str_contains($base, 'dinheiro') || str_contains($base, 'especie')) return 'dinheiro';
    if (str_contains($base, 'boleto') || str_contains($base, 'cobranca')) return 'boleto';
    if (str_contains($base, 'transferencia') || str_contains($base, 'ted') || str_contains($base, 'doc')) return 'transferencia';
    return 'outro';
}

function sf_type_guess(string $line, float $amount): string
{
    $base = sf_search_text($line);
    if (str_contains($base, 'pensao') || str_contains($base, 'pensao alimenticia')) {
        return str_contains($base, 'pensao recebida') ? 'entrada' : 'saida';
    }
    foreach (['fatura do cartao', 'fatura cartao', 'pagamento da fatura', 'pagamento fatura', 'total da fatura'] as $term) {
        if (str_contains($base, $term)) {
            return 'fatura_cartao';
        }
    }
    foreach (['recebido', 'salario', 'adiantamento', 'deposito', 'credito em conta', 'pix recebido', 'entrada', 'ted recebido', 'transferencia recebida'] as $term) {
        if (str_contains($base, $term)) {
            return 'entrada';
        }
    }
    if (str_contains($base, 'cartao') || str_contains($base, 'credito') || str_contains($base, 'maquininha') || str_contains($base, 'visa') || str_contains($base, 'mastercard') || str_contains($base, 'elo')) {
        return 'cartao_credito';
    }
    return 'saida';
}

function sf_search_text(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    return preg_replace('/\s+/', ' ', $converted !== false ? $converted : $text) ?: $text;
}

function sf_card_last4_guess(string $text): string
{
    $patterns = [
        '/(?:final|ultimos?\s*4|termina(?:do)?|cart(?:a|ã)o|card)[^\d]{0,24}(\d{4})(?!\d)/iu',
        '/(?:[*xX•]{2,}[\s.-]*)+(\d{4})(?!\d)/u',
        '/(?:\d{4}[ .-]+){2,3}(\d{4})(?!\d)/',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $match)) {
            return substr(preg_replace('/\D+/', '', (string)$match[1]), -4);
        }
    }
    return '';
}

function sf_import_movement_is_card(array $movement): bool
{
    $type = sf_text($movement['type'] ?? '', 40);
    $method = sf_text($movement['paymentMethod'] ?? '', 40);
    $last4 = substr(preg_replace('/\D+/', '', (string)($movement['cardLast4'] ?? '')), -4);

    return (int)($movement['cardId'] ?? 0) > 0
        || $last4 !== ''
        || (!in_array($method, ['pix', 'debito'], true)
            && ($method === 'credito' || in_array($type, ['cartao_credito', 'fatura_cartao'], true)));
}

function sf_amount_matches(string $line): array
{
    $pattern = '/[+-]?\s*(?:R\$\s*)?(?:(?:\d{1,3}(?:[.\s]\d{3})+|\d+),\d{2}|(?:\d{1,3}(?:,\d{3})+|\d+)\.\d{2})/u';
    preg_match_all($pattern, $line, $matches, PREG_OFFSET_CAPTURE);
    return $matches[0] ?? [];
}

function sf_real_amount_matches(string $line): array
{
    $pattern = '/[+-]?\s*R\$\s*(?:(?:\d{1,3}(?:\.\d{3})+|\d+),\d{2}|(?:\d{1,3}(?:,\d{3})+|\d+)\.\d{2})/u';
    preg_match_all($pattern, $line, $matches, PREG_OFFSET_CAPTURE);
    return $matches[0] ?? [];
}

function sf_receipt_like_by_currency(string $text): bool
{
    $base = sf_search_text($text);
    foreach (['extrato', 'saldo', 'limite', 'movimentacao', 'lancamento', 'historico', 'fatura', 'parcela'] as $signal) {
        if (str_contains($base, $signal)) return false;
    }
    if (preg_match_all('/\b\d{2}[\/.\-]\d{2}(?:[\/.\-]\d{2,4})?\b/u', $text) > 1) {
        return false;
    }
    return preg_match_all('/R\$\s*/iu', $text) >= 2;
}

function sf_select_amount_match(array $matches, string $line = ''): array
{
    if (!$matches) return ['', 0];
    $base = sf_search_text($line);
    if (str_contains($base, 'total') || str_contains($base, 'valor pago') || str_contains($base, 'valor da compra')) {
        return $matches[count($matches) - 1];
    }
    foreach ($matches as $match) {
        if (str_contains((string)$match[0], 'R$')) return $match;
    }
    foreach ($matches as $match) {
        if (str_contains((string)$match[0], '-')) return $match;
    }
    return $matches[0];
}

function sf_extract_time(string $line): string
{
    if (preg_match('/\b([01]?\d|2[0-3])\s*[:h]\s*([0-5]\d)(?:\s*[:h]\s*([0-5]\d))?\b/u', $line, $match)) {
        return sprintf('%02d:%02d', (int)$match[1], (int)$match[2]);
    }
    return '';
}

function sf_installment_guess(string $text): array
{
    $base = sf_search_text($text);
    if (!str_contains($base, 'parcela') && !str_contains($base, 'parc.') && !str_contains($base, 'prestacao')) {
        return ['number' => 1, 'count' => 1];
    }
    if (preg_match('/(?:parcela|parc\.?|prestacao)\s*(?:n[ºo]?\s*)?(\d{1,2})\s*(?:\/|de|of)\s*(\d{1,2})\b/iu', $base, $match)) {
        $number = max(1, min(60, (int)$match[1]));
        $count = max($number, min(60, (int)$match[2]));
        return ['number' => $number, 'count' => $count];
    }
    return ['number' => 1, 'count' => 1];
}

function sf_date_guess(string $line, string $referenceMonth): string
{
    if (preg_match('/\b(\d{4})[\/-](\d{2})[\/-](\d{2})\b/', $line, $m)) {
        $year = (int)$m[1];
        $month = (int)$m[2];
        $day = (int)$m[3];
        if (checkdate($month, $day, $year)) {
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
    }
    if (preg_match('/\b(\d{2})[\/.\-](\d{2})(?:[\/.\-](\d{2,4}))?\b/', $line, $m)) {
        $day = (int)$m[1];
        $month = (int)$m[2];
        $year = isset($m[3]) && $m[3] !== '' ? (int)$m[3] : (int)substr($referenceMonth, 0, 4);
        if ($year < 100) {
            $year += 2000;
        }
        if (checkdate($month, $day, $year)) {
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
    }
    return date('Y-m-d');
}

function sf_period_guess_by_date(string $date): string
{
    return ((int)substr($date, 8, 2)) <= 20 ? 'dia20' : 'quinto_util';
}

function sf_description_guess(string $line, string $rawAmount): string
{
    $description = preg_replace('/\b\d{4}[\/.\-]\d{2}[\/.\-]\d{2}\b/', ' ', $line);
    $description = preg_replace('/\b\d{2}[\/.\-]\d{2}(?:[\/.\-]\d{2,4})?\b/', ' ', (string)$description);
    $description = preg_replace('/\b(?:[01]?\d|2[0-3])\s*[:h]\s*[0-5]\d(?:\s*[:h]\s*[0-5]\d)?\b/u', ' ', (string)$description);
    $description = str_replace($rawAmount, ' ', (string)$description);
    $description = preg_replace('/[+-]?\s*(?:R\$\s*)?(?:(?:\d{1,3}(?:[.\s]\d{3})+|\d+),\d{2}|(?:\d{1,3}(?:,\d{3})+|\d+)\.\d{2})/u', ' ', (string)$description);
    $description = preg_replace('/\b(?:saldo anterior|saldo final|saldo disponivel|limite disponivel|compra aprovada|transacao aprovada|lancamento)\b\s*[:\-|]?/iu', ' ', (string)$description);
    $description = preg_replace('/\bfinal\s*\d{4}\b/iu', ' ', (string)$description);
    $description = preg_replace('/\b(?:parcela|parc\.?|prestacao)\s*\d{1,2}\s*(?:\/|de|of)\s*\d{1,2}\b/iu', ' ', (string)$description);
    $description = preg_replace('/\b(?:saldo|saldo atual|saldo disponivel|limite disponivel)\b/iu', ' ', (string)$description);
    $description = preg_replace('/\b(?:compra\s+(?:no\s+)?(?:cartao|credito)|cartao\s+(?:de\s+)?credito|cartao|credito|debito)\b/iu', ' ', (string)$description);
    $description = preg_replace('/^\s*(?:pix|transferencia(?:\s+(?:recebida|enviada))?|ted|doc)\s*[-:|]?\s*/iu', '', (string)$description);
    $description = preg_replace('/\s*[|;]+\s*/', ' ', (string)$description);
    $description = preg_replace('/\s+/', ' ', (string)$description);
    return sf_text(trim((string)$description, " -|:\t"), 255);
}

function sf_party_label_pattern(): string
{
    return '(?:nome\s+(?:do|da)\s+(?:recebedor|recebedora|favorecido|favorecida|benefici[aá]ri[oa]|destinat[aá]ri[oa]|estabelecimento|comerciante)|recebedor(?:a)?|favorecid(?:o|a)|benefici[aá]ri[oa]|destinat[aá]ri[oa]|estabelecimento|comerciante|lojista|merchant)';
}

function sf_has_party_label(string $line): bool
{
    return preg_match('/\b' . sf_party_label_pattern() . '\b/iu', $line) === 1
        || preg_match('/^\s*nome\s*[:\-]/iu', $line) === 1;
}

function sf_party_name_clean(string $value, string $rawAmount = ''): string
{
    $name = $value;
    if ($rawAmount !== '') $name = str_replace($rawAmount, ' ', $name);
    $name = preg_replace('/[+-]?\s*R\$\s*(?:(?:\d{1,3}(?:\.\d{3})+|\d+),\d{2}|(?:\d{1,3}(?:,\d{3})+|\d+)\.\d{2})/u', ' ', (string)$name);
    $name = preg_replace('/\b\d{4}[\/\.\-]\d{2}[\/\.\-]\d{2}\b/', ' ', (string)$name);
    $name = preg_replace('/\b\d{2}[\/\.\-]\d{2}(?:[\/\.\-]\d{2,4})?\b/', ' ', (string)$name);
    $name = preg_replace('/\b(?:[01]?\d|2[0-3])\s*[:h]\s*[0-5]\d(?:\s*[:h]\s*[0-5]\d)?\b/u', ' ', (string)$name);
    $name = preg_replace('/\b(?:cpf|cnpj|telefone|tel|cep|nsu|autorizacao|chave)\b\s*[:\-]?\s*[\w.\/-]+/iu', ' ', (string)$name);
    $name = preg_replace('/^\s*' . sf_party_label_pattern() . '\s*[:\-|]?\s*/iu', '', (string)$name);
    $name = preg_replace('/^\s*(?:pix|pagamento|transferencia|transferência|ted|doc|ted\/doc)\s*(?:enviado|enviada|recebido|recebida|realizado|realizada|efetuado|efetuada|aprovado|aprovada)?\s*(?:para|a|de)?\s*[:\-|]?\s*/iu', '', (string)$name);
    $name = preg_replace('/\s+(?:valor|data|hora|cpf|cnpj|pagamento|forma de pagamento)\s*[:\-]?\s*$/iu', '', (string)$name);
    $name = preg_replace('/\s*[|;]+\s*/', ' ', (string)$name);
    $name = preg_replace('/\s+/', ' ', (string)$name);
    $name = trim((string)$name, " -|:\t");
    $base = sf_search_text($name);
    if ($name === '' || mb_strlen($name, 'UTF-8') < 3) return '';
    if (preg_match('/\b(?:comprovante|transacao|pagamento|valor|total|saldo|limite|data|hora|fatura|cartao|credito|debito|pix|boleto|parcela|cpf|cnpj|autorizacao|nsu|chave|descricao|produto|quantidade|qtd|forma|recebedor|favorecido|beneficiario|destinatario)\b/iu', $base)) {
        return '';
    }
    return sf_text($name, 180);
}

function sf_labeled_party_guess(array $lines): string
{
    $pattern = sf_party_label_pattern();
    $lineCount = count($lines);
    foreach ($lines as $index => $line) {
        $line = sf_text($line, 500);
        if ($line === '') continue;
        if (preg_match('/^\s*(?:nome\s*[:\-]|' . $pattern . '\s*[:\-]?)\s*(.*?)\s*$/iu', $line, $match)) {
            $candidate = sf_party_name_clean((string)($match[1] ?? ''));
            if ($candidate !== '') return $candidate;
            for ($next = $index + 1; $next < min($lineCount, $index + 4); $next++) {
                $candidate = sf_party_name_clean((string)$lines[$next]);
                if ($candidate !== '') return $candidate;
            }
        }
    }
    return '';
}

function sf_party_name_from_line(string $line, string $rawAmount = ''): string
{
    $labeled = sf_labeled_party_guess([$line]);
    return $labeled !== '' ? $labeled : sf_party_name_clean($line, $rawAmount);
}

function sf_is_statement_noise(string $line): bool
{
    $base = sf_search_text($line);
    return str_contains($base, 'saldo anterior')
        || str_contains($base, 'saldo final')
        || str_contains($base, 'saldo disponivel')
        || str_contains($base, 'saldo da conta')
        || str_contains($base, 'saldo do dia')
        || str_contains($base, 'limite disponivel')
        || str_contains($base, 'limite de credito')
        || str_contains($base, 'limite total')
        || str_starts_with(trim($base), 'saldo:')
        || str_starts_with(trim($base), 'saldo ')
        || str_starts_with(trim($base), 'limite ');
}

function sf_receipt_has_item_signal(string $text): bool
{
    $base = sf_search_text($text);
    $signals = [
        'comprovante', 'pagamento realizado', 'pagamento efetuado',
        'compra aprovada', 'nota fiscal', 'nf-e', 'nfe', 'cupom fiscal', 'cupom nao fiscal',
        'subtotal', 'total a pagar', 'valor total', 'valor da compra',
        'forma de pagamento', 'valor pago', 'troco', 'qtd',
        'quantidade', 'itens da compra', 'produto', 'descricao do produto',
    ];
    foreach ($signals as $signal) {
        if (str_contains($base, $signal)) return true;
    }
    $dateCount = preg_match_all('/\b\d{2}[\/\.\-]\d{2}(?:[\/\.\-]\d{2,4})?\b/u', $text);
    if (str_contains($base, 'estabelecimento')
        && $dateCount <= 1
        && !preg_match('/\b(?:extrato|movimentacao|lancamento|historico|fatura|saldo|limite)\b/iu', $base)) {
        return true;
    }
    return sf_receipt_like_by_currency($text);
}

function sf_receipt_product_line(string $line): ?array
{
    $matches = sf_real_amount_matches($line);
    if (!$matches || sf_is_statement_noise($line)) return null;
    $base = sf_search_text($line);
    foreach (['total', 'subtotal', 'desconto', 'acrescimo', 'troco', 'pagamento', 'forma de pagamento', 'cartao', 'pix', 'debito', 'credito', 'cnpj', 'cpf', 'chave', 'autorizacao', 'nsu', 'parcela', 'imposto', 'icms', 'iss', 'pis', 'cofins', 'aliquota', 'valor', 'saldo', 'limite'] as $term) {
        if (str_contains($base, $term)) return null;
    }
    if (preg_match('/^\s*(?:\d{1,2}[\/.\-]){2,3}\d{0,4}\s*$/', trim($line))) return null;

    $selected = $matches[count($matches) - 1];
    $rawAmount = trim((string)$selected[0]);
    $amount = abs(sf_money($rawAmount));
    $description = sf_description_guess($line, $rawAmount);
    if ($amount <= 0 || mb_strlen($description, 'UTF-8') < 2) return null;

    return [
        'description' => $description,
        'amount' => $amount,
    ];
}

function sf_receipt_merchant_guess(array $lines): string
{
    $labeled = sf_labeled_party_guess($lines);
    if ($labeled !== '') return $labeled;
    foreach ($lines as $line) {
        $line = sf_text($line, 180);
        if ($line === '' || sf_amount_matches($line)) continue;
        $base = sf_search_text($line);
        if (preg_match('/\b\d{2}[\/.\-]\d{2}(?:[\/.\-]\d{2,4})?\b/', $line)) continue;
        if (preg_match('/\b(?:cnpj|cpf|telefone|tel|endereco|rua|avenida|cep|nota fiscal|cupom|chave|autorizacao|nsu|data|hora|qtd|quantidade|produto|descricao)\b/iu', $base)) continue;
        $candidate = sf_party_name_clean($line);
        if ($candidate !== '') return $candidate;
    }
    return '';
}

function sf_parse_receipt(string $text, string $referenceMonth, ?string $preferredPeriod): array
{
    if (!sf_receipt_has_item_signal($text)) return [];
    $lines = preg_split('/\R+/', str_replace(["\r", "\xC2\xA0"], ['', ' '], $text));
    $products = [];
    $total = 0.0;
    foreach ($lines as $line) {
        $line = sf_text($line, 500);
        if ($line === '') continue;
        $matches = sf_real_amount_matches($line);
        if (!$matches) continue;
        $base = sf_search_text($line);
        $isExplicitTotal = !str_contains($base, 'subtotal')
            && (str_contains($base, 'total')
                || str_contains($base, 'valor pago')
                || str_contains($base, 'valor da compra')
                || str_contains($base, 'total a pagar')
                || ((str_starts_with($base, 'valor ') || str_starts_with($base, 'valor:'))
                    && !str_contains($base, 'unitario')
                    && !str_contains($base, 'unitaria')));
        if ($isExplicitTotal) {
            $selected = $matches[count($matches) - 1];
            $total = abs(sf_money(trim((string)$selected[0])));
            continue;
        }
        $product = sf_receipt_product_line($line);
        if ($product) $products[] = $product;
    }

    if ($total <= 0 && $products) {
        $total = round(array_sum(array_column($products, 'amount')), 2);
    }
    if ($total <= 0) return [];
    $merchant = sf_receipt_merchant_guess($lines);
    $details = implode('; ', array_map(static function (array $product): string {
        return $product['description'] . ' - R$ ' . number_format((float)$product['amount'], 2, ',', '.');
    }, $products));
    $description = $merchant !== '' ? $merchant : 'Gasto reconhecido';
    if ($details !== '') $description .= ' | Itens: ' . $details;
    $method = sf_payment_guess($text);
    $type = sf_type_guess($text, -$total);
    if ($method === 'credito') $type = 'cartao_credito';
    if ($type === 'entrada') $type = 'saida';
    $date = sf_date_guess($text, $referenceMonth);
    $movementTime = sf_extract_time($text);
    $cardLast4 = sf_card_last4_guess($text);
    $installments = sf_installment_guess($text);

    return [[
        'type' => $type,
        'description' => sf_text($description, 255),
        'category' => sf_category_guess($text),
        'amount' => $total,
        'movementDate' => $date,
        'movementTime' => $movementTime,
        'paymentMethod' => $method,
        'periodType' => $preferredPeriod ?: sf_period_guess_by_date($date),
        'referenceMonth' => $referenceMonth,
        'cardLast4' => $cardLast4,
        'installmentNumber' => $installments['number'],
        'installmentCount' => $installments['count'],
        'installmentAmount' => $total,
        'itemDetails' => sf_text($details, 800),
        'status' => 'pago',
        'source' => 'extrato',
        'notes' => $details !== ''
            ? 'Comprovante com produtos identificados: ' . sf_text($details, 700)
            : 'Comprovante com valor em reais identificado.',
    ]];
}

function sf_parse_statement(string $text, string $referenceMonth, ?string $preferredPeriod): array
{
    $receiptItems = sf_parse_receipt($text, $referenceMonth, $preferredPeriod);
    if ($receiptItems) return $receiptItems;

    $lines = preg_split('/\R+/', str_replace(["\r", "\xC2\xA0"], ['', ' '], $text));
    $items = [];
    $preferredPeriod = $preferredPeriod ? sf_period($preferredPeriod) : null;
    $documentCardLast4 = '';
    foreach ($lines as $documentLine) {
        $documentLine = sf_text($documentLine, 500);
        $lineCardLast4 = sf_card_last4_guess($documentLine);
        if ($lineCardLast4 !== '' && empty(sf_amount_matches($documentLine)) && preg_match('/cart(?:a|ã)o|fatura|final|card/iu', $documentLine)) {
            $documentCardLast4 = $lineCardLast4;
            break;
        }
    }
    $pendingLine = '';

    foreach ($lines as $line) {
        $line = sf_text($line, 500);
        if ($line === '') {
            continue;
        }

        $matches = sf_amount_matches($line);
        if (empty($matches)) {
            if (preg_match('/\b(?:\d{2}[\/.\-]\d{2}|\d{4}[\/.\-]\d{2}[\/.\-]\d{2})\b/', $line) || sf_has_party_label($line)) {
                $pendingLine = sf_text(trim($pendingLine . ' ' . $line), 700);
            }
            continue;
        }

        $candidateLine = trim($pendingLine . ' ' . $line);
        $pendingLine = '';
        if (sf_is_statement_noise($candidateLine)) {
            continue;
        }

        // Em extratos com coluna de saldo, o primeiro valor costuma ser a transacao
        // e os seguintes sao saldo/limite. Valores explicitos com sinal tem prioridade.
        $selected = sf_select_amount_match($matches, $candidateLine);
        $rawAmount = trim((string)$selected[0]);
        $signedAmount = sf_money($rawAmount);
        if (abs($signedAmount) <= 0) {
            continue;
        }

        $date = sf_date_guess($candidateLine, $referenceMonth);
        $movementTime = sf_extract_time($candidateLine);
        $cardLast4 = sf_card_last4_guess($candidateLine) ?: $documentCardLast4;
        $installments = sf_installment_guess($candidateLine);
        $type = sf_type_guess($candidateLine, $signedAmount);
        $description = sf_party_name_from_line($candidateLine, $rawAmount);
        if ($description === '') $description = sf_description_guess($candidateLine, $rawAmount);
        if ($description === '') {
            $description = $type === 'entrada' ? 'Entrada reconhecida' : 'Gasto reconhecido';
        }

        $items[] = [
            'type' => $type,
            'description' => $description,
            'category' => sf_category_guess($candidateLine),
            'amount' => abs($signedAmount),
            'movementDate' => $date,
            'movementTime' => $movementTime,
            'paymentMethod' => sf_payment_guess($candidateLine),
            'periodType' => $preferredPeriod ?: sf_period_guess_by_date($date),
            'referenceMonth' => $referenceMonth,
            'cardLast4' => $cardLast4,
            'installmentNumber' => $installments['number'],
            'installmentCount' => $installments['count'],
            'installmentAmount' => abs($signedAmount),
            'status' => 'pago',
            'source' => 'extrato',
            'notes' => 'Reconhecido por importacao de extrato ou comprovante. Linha: ' . sf_text($candidateLine, 600),
        ];
    }

    return array_slice($items, 0, 100);
}

function sf_enrich_import_movements(PDO $pdo, int $userId, array $movements): array
{
    $stmt = $pdo->prepare('SELECT id, nickname, last4 FROM cards WHERE user_id = :user_id AND active = 1 ORDER BY id ASC');
    $stmt->execute([':user_id' => $userId]);
    $cardsByLast4 = [];
    $cardsById = [];
    foreach ($stmt->fetchAll() as $card) {
        $cardsById[(int)$card['id']] = $card;
        $last4 = substr(preg_replace('/\D+/', '', (string)$card['last4']), -4);
        if ($last4 === '') continue;
        $cardsByLast4[$last4][] = $card;
    }

    foreach ($movements as &$movement) {
        if (!is_array($movement)) continue;
        $context = sf_text(($movement['rawText'] ?? '') . ' ' . ($movement['description'] ?? '') . ' ' . ($movement['itemDetails'] ?? '') . ' ' . ($movement['notes'] ?? ''), 1200);
        $movement['movementDate'] = sf_date($movement['movementDate'] ?? null);
        $movement['referenceMonth'] = substr($movement['movementDate'], 0, 7) ?: sf_month($movement['referenceMonth'] ?? null);
        $last4 = substr(preg_replace('/\D+/', '', (string)($movement['cardLast4'] ?? '')), -4);
        if ($last4 === '') $last4 = sf_card_last4_guess($context);
        $movement['cardLast4'] = $last4;
        $movement['movementTime'] = sf_time($movement['movementTime'] ?? '');
        $installmentCount = max(1, min(60, (int)($movement['installmentCount'] ?? 1)));
        $movement['installmentCount'] = $installmentCount;
        $movement['installmentNumber'] = max(1, min($installmentCount, (int)($movement['installmentNumber'] ?? 1)));
        $movement['installmentAmount'] = abs(sf_money($movement['amount'] ?? $movement['installmentAmount'] ?? 0));
        $movement['paymentMethod'] = sf_payment_guess((string)($movement['paymentMethod'] ?? '') . ' ' . $context);

        $cardMatches = $last4 !== '' ? ($cardsByLast4[$last4] ?? []) : [];
        $cardId = (int)($movement['cardId'] ?? 0);
        if ($cardId > 0 && !sf_card_belongs($pdo, $cardId, $userId)) $cardId = 0;
        if ($cardId <= 0 && count($cardMatches) === 1) $cardId = (int)$cardMatches[0]['id'];
        $selectedCard = $cardId > 0 ? ($cardsById[$cardId] ?? null) : null;
        $movement['cardId'] = $cardId > 0 ? $cardId : null;
        $movement['cardNickname'] = (string)($selectedCard['nickname'] ?? '');
        $movement['cardMatch'] = $cardId > 0 ? 'matched' : ($last4 !== '' ? (count($cardMatches) > 1 ? 'ambiguous' : 'not_found') : 'none');
        if ($cardId > 0 || $last4 !== '') {
            $movement['paymentMethod'] = 'credito';
        }

        $type = sf_text($movement['type'] ?? '', 40);
        if ($type === '' || !in_array($type, ['entrada', 'saida', 'cartao_credito', 'fatura_cartao', 'reserva', 'ajuste'], true)) {
            $type = sf_type_guess($context, sf_money($movement['amount'] ?? 0));
        }
        if ($movement['cardId'] || $movement['paymentMethod'] === 'credito') {
            if ($type !== 'fatura_cartao') $type = 'cartao_credito';
        }
        if (in_array($movement['paymentMethod'], ['pix', 'debito'], true)
            && !$movement['cardId']
            && $movement['cardLast4'] === '') {
            $type = 'saida';
        }
        $normalizedContext = sf_search_text($context);
        $isPension = str_contains($normalizedContext, 'pensao');
        if ($isPension && !str_contains($normalizedContext, 'pensao recebida')) {
            $movement['category'] = 'Pensao';
            $type = 'saida';
        }
        $movement['type'] = $type;
        $period = strtolower(trim((string)($movement['periodType'] ?? '')));
        if (!in_array($period, ['dia20', 'quinto_util'], true)) {
            $period = sf_period_guess_by_date($movement['movementDate']);
        }
        $movement['periodType'] = $period;
        $movement['periodTypeConfirmed'] = !empty($movement['periodTypeConfirmed']);
        $movement['needsPeriodSelection'] = !$movement['periodTypeConfirmed']
            && !sf_import_movement_is_card($movement)
            && in_array($movement['paymentMethod'], ['pix', 'debito'], true);
        $movement['description'] = sf_text($movement['description'] ?? 'Gasto reconhecido', 255);
        $itemDetails = sf_text($movement['itemDetails'] ?? '', 800);
        if ($itemDetails !== '') {
            $movement['itemDetails'] = $itemDetails;
            $movement['notes'] = sf_text('Produtos identificados: ' . $itemDetails . '. ' . ($movement['notes'] ?? ''), 800);
        } else {
            $movement['notes'] = sf_text($movement['notes'] ?? 'Lancado por importacao revisada.', 800);
        }
    }
    unset($movement);
    return $movements;
}

function sf_save_import(PDO $pdo, int $userId, string $type, string $fileName, string $text, array $movements): int
{
    $stmt = $pdo->prepare("
        INSERT INTO imports (user_id, import_type, file_name, extracted_text, movements_json, status, created_at)
        VALUES (:user_id, :import_type, :file_name, :extracted_text, :movements_json, :status, :created_at)
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':import_type' => sf_text($type, 60),
        ':file_name' => sf_text($fileName, 190),
        ':extracted_text' => substr($text, 0, 20000),
        ':movements_json' => json_encode($movements, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ':status' => 'confirmado',
        ':created_at' => sf_now(),
    ]);
    return (int)$pdo->lastInsertId();
}

try {
    $pdo = sf_db();
    $user = sf_require_user($pdo);
    $userId = (int)$user['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = sf_text($_GET['action'] ?? 'dashboard', 60);
        $month = sf_month($_GET['month'] ?? null);

        if ($action === 'calendar') {
            $year = (int)($_GET['year'] ?? date('Y'));
            $calendar = [];
            for ($m = 1; $m <= 12; $m++) {
                $reference = sprintf('%04d-%02d', $year, $m);
                $calendar[] = [
                    'month' => $reference,
                    'periods' => sf_month_periods($reference),
                ];
            }
            sf_json(['ok' => true, 'year' => $year, 'calendar' => $calendar]);
        }

        if ($action === 'bank_status') {
            sf_json([
                'ok' => true,
                'ready' => true,
                'mode' => 'prepared_connector',
                'message' => 'API preparada para receber transacoes por Open Finance/Pix mediante consentimento bancario.',
                'acceptedFields' => ['description', 'amount', 'movementDate', 'movementTime', 'paymentMethod', 'cardLast4', 'cardId', 'periodType', 'referenceMonth'],
            ]);
        }

        sf_json(sf_dashboard($pdo, $user, $month));
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sf_json(['ok' => false, 'message' => 'Metodo nao permitido.'], 405);
    }

    $input = sf_input();
    $action = sf_text($input['action'] ?? '', 60);
    $month = sf_month($input['month'] ?? ($input['referenceMonth'] ?? null));

    if ($action === 'save_transaction') {
        $transaction = is_array($input['transaction'] ?? null) ? $input['transaction'] : [];
        $scheduledMonths = [];
        if ((int)($transaction['id'] ?? 0) <= 0 && sf_is_card_purchase($transaction)) {
            $scheduled = sf_apply_credit_schedule($transaction);
            $count = max(1, min(60, (int)($transaction['installmentCount'] ?? 1)));
            $baseMonth = sf_month($scheduled['referenceMonth'] ?? null);
            for ($number = 0; $number < $count; $number++) {
                $scheduledMonths[] = sf_shift_month($baseMonth, $number);
            }
            $scheduledMonths = array_values(array_unique($scheduledMonths));
        }
        sf_save_transaction_installments($pdo, $userId, $transaction, true);
        $dashboard = sf_dashboard($pdo, $user, $month);
        $dashboard['scheduledMonths'] = $scheduledMonths;
        sf_json($dashboard);
    }

    if ($action === 'delete_transaction') {
        $stmt = $pdo->prepare('DELETE FROM transactions WHERE id = :id AND user_id = :user_id');
        $stmt->execute([':id' => (int)($input['id'] ?? 0), ':user_id' => $userId]);
        sf_json(sf_dashboard($pdo, $user, $month));
    }

    if ($action === 'delete_income') {
        $stmt = $pdo->prepare('DELETE FROM incomes WHERE id = :id AND user_id = :user_id');
        $stmt->execute([':id' => (int)($input['id'] ?? 0), ':user_id' => $userId]);
        sf_json(sf_dashboard($pdo, $user, $month));
    }

    if ($action === 'reset_finances') {
        if (empty($input['confirm'])) {
            sf_json(['ok' => false, 'message' => 'Confirmacao obrigatoria para zerar os dados financeiros.'], 422);
        }
        $pdo->beginTransaction();
        try {
            foreach (['transactions', 'incomes', 'cards', 'goals', 'imports', 'bank_connections'] as $table) {
                $stmt = $pdo->prepare("DELETE FROM {$table} WHERE user_id = :user_id");
                $stmt->execute([':user_id' => $userId]);
            }
            $pdo->commit();
        } catch (Throwable $error) {
            $pdo->rollBack();
            throw $error;
        }
        sf_json(sf_dashboard($pdo, $user, $month));
    }

    if ($action === 'save_income') {
        $income = is_array($input['income'] ?? null) ? $input['income'] : [];
        $referenceMonth = sf_month($income['referenceMonth'] ?? $month);
        $periodType = sf_period($income['periodType'] ?? null);
        $gross = abs(sf_money($income['grossSalary'] ?? 0));
        $benefits = abs(sf_money($income['benefits'] ?? 0));
        $night = abs(sf_money($income['nightShift'] ?? 0));
        $hazard = abs(sf_money($income['hazardPay'] ?? 0));
        $extra = abs(sf_money($income['extraIncome'] ?? 0));
        $dia20SemDesconto = $periodType === 'dia20';
        $otherDiscounts = $dia20SemDesconto ? 0.0 : abs(sf_money($income['otherDiscounts'] ?? 0));
        $baseInss = $gross + $night + $hazard + $extra;
        $inss = $dia20SemDesconto ? 0.0 : sf_inss_2026($baseInss);
        $transport = $dia20SemDesconto ? 0.0 : (!empty($income['transportActive']) ? abs(sf_money($income['transportManual'] ?? 0)) : 0.0);
        if (!$dia20SemDesconto && !empty($income['transportActive']) && $transport <= 0) {
            $transport = round($gross * 0.06, 2);
        }
        $net = round($gross + $benefits + $night + $hazard + $extra - $inss - $transport - $otherDiscounts, 2);
        $paymentDate = sf_date($income['paymentDate'] ?? sf_period_due_date($referenceMonth, $periodType));
        $payload = [
            'baseInss' => $baseInss,
            'transportActive' => !empty($income['transportActive']),
            'sourceInss' => 'Gov.br INSS - tabela de contribuicao mensal atualizada em 13/01/2026',
            'notes' => sf_text($income['notes'] ?? '', 800),
        ];

        $existing = $pdo->prepare('SELECT id, created_at FROM incomes WHERE user_id = :user_id AND reference_month = :reference_month AND period_type = :period_type LIMIT 1');
        $existing->execute([':user_id' => $userId, ':reference_month' => $referenceMonth, ':period_type' => $periodType]);
        $row = $existing->fetch();
        $now = sf_now();

        if ($row) {
            $stmt = $pdo->prepare("
                UPDATE incomes
                   SET payment_date = :payment_date,
                       gross_salary = :gross_salary,
                       benefits = :benefits,
                       night_shift = :night_shift,
                       hazard_pay = :hazard_pay,
                       extra_income = :extra_income,
                       inss_discount = :inss_discount,
                       transport_discount = :transport_discount,
                       other_discounts = :other_discounts,
                       net_amount = :net_amount,
                       payload_json = :payload_json,
                       updated_at = :updated_at
                 WHERE id = :id
                   AND user_id = :user_id
            ");
            $stmt->execute([
                ':id' => (int)$row['id'],
                ':user_id' => $userId,
                ':payment_date' => $paymentDate,
                ':gross_salary' => $gross,
                ':benefits' => $benefits,
                ':night_shift' => $night,
                ':hazard_pay' => $hazard,
                ':extra_income' => $extra,
                ':inss_discount' => $inss,
                ':transport_discount' => $transport,
                ':other_discounts' => $otherDiscounts,
                ':net_amount' => $net,
                ':payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ':updated_at' => $now,
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO incomes (
                    user_id, reference_month, period_type, payment_date, gross_salary,
                    benefits, night_shift, hazard_pay, extra_income, inss_discount,
                    transport_discount, other_discounts, net_amount, payload_json,
                    created_at, updated_at
                ) VALUES (
                    :user_id, :reference_month, :period_type, :payment_date, :gross_salary,
                    :benefits, :night_shift, :hazard_pay, :extra_income, :inss_discount,
                    :transport_discount, :other_discounts, :net_amount, :payload_json,
                    :created_at, :updated_at
                )
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':reference_month' => $referenceMonth,
                ':period_type' => $periodType,
                ':payment_date' => $paymentDate,
                ':gross_salary' => $gross,
                ':benefits' => $benefits,
                ':night_shift' => $night,
                ':hazard_pay' => $hazard,
                ':extra_income' => $extra,
                ':inss_discount' => $inss,
                ':transport_discount' => $transport,
                ':other_discounts' => $otherDiscounts,
                ':net_amount' => $net,
                ':payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
        }

        sf_json(sf_dashboard($pdo, $user, $referenceMonth));
    }

    if ($action === 'save_payslip') {
        $payslip = is_array($input['payslip'] ?? null) ? $input['payslip'] : [];
        $referenceMonth = sf_month($payslip['referenceMonth'] ?? $month);
        $periodType = sf_period($payslip['periodType'] ?? null);
        $net = abs(sf_money($payslip['netAmount'] ?? 0));
        if ($net <= 0) {
            sf_json(['ok' => false, 'message' => 'Informe um ganho liquido maior que zero.'], 422);
        }
        $paymentDate = sf_date($payslip['paymentDate'] ?? sf_period_due_date($referenceMonth, $periodType));
        $payload = [
            'source' => 'holerite',
            'fileName' => sf_text($payslip['fileName'] ?? '', 190),
            'notes' => sf_text($payslip['notes'] ?? '', 800),
        ];

        $existing = $pdo->prepare('SELECT id FROM incomes WHERE user_id = :user_id AND reference_month = :reference_month AND period_type = :period_type LIMIT 1');
        $existing->execute([':user_id' => $userId, ':reference_month' => $referenceMonth, ':period_type' => $periodType]);
        $row = $existing->fetch();
        $now = sf_now();

        if ($row) {
            $stmt = $pdo->prepare("
                UPDATE incomes
                   SET payment_date = :payment_date,
                       gross_salary = 0,
                       benefits = 0,
                       night_shift = 0,
                       hazard_pay = 0,
                       extra_income = 0,
                       inss_discount = 0,
                       transport_discount = 0,
                       other_discounts = 0,
                       net_amount = :net_amount,
                       payload_json = :payload_json,
                       updated_at = :updated_at
                 WHERE id = :id
                   AND user_id = :user_id
            ");
            $stmt->execute([
                ':id' => (int)$row['id'],
                ':user_id' => $userId,
                ':payment_date' => $paymentDate,
                ':net_amount' => $net,
                ':payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ':updated_at' => $now,
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO incomes (
                    user_id, reference_month, period_type, payment_date, gross_salary,
                    benefits, night_shift, hazard_pay, extra_income, inss_discount,
                    transport_discount, other_discounts, net_amount, payload_json,
                    created_at, updated_at
                ) VALUES (
                    :user_id, :reference_month, :period_type, :payment_date, 0,
                    0, 0, 0, 0, 0,
                    0, 0, :net_amount, :payload_json,
                    :created_at, :updated_at
                )
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':reference_month' => $referenceMonth,
                ':period_type' => $periodType,
                ':payment_date' => $paymentDate,
                ':net_amount' => $net,
                ':payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
        }

        sf_json(sf_dashboard($pdo, $user, $referenceMonth));
    }

    if ($action === 'save_card') {
        $card = is_array($input['card'] ?? null) ? $input['card'] : [];
        $id = (int)($card['id'] ?? 0);
        $number = preg_replace('/\D+/', '', (string)($card['number'] ?? $card['last4'] ?? ''));
        $existing = null;
        if ($id > 0) {
            $existingStmt = $pdo->prepare('SELECT bin4, last4, product_name, cardholder_name, expiry_date FROM cards WHERE id = :id AND user_id = :user_id LIMIT 1');
            $existingStmt->execute([':id' => $id, ':user_id' => $userId]);
            $existing = $existingStmt->fetch() ?: null;
        }
        $last4 = $number !== '' ? substr($number, -4) : (string)($existing['last4'] ?? '');
        $bin4 = strlen($number) >= 8
            ? substr($number, 0, 4)
            : substr(preg_replace('/\D+/', '', (string)($card['bin4'] ?? $existing['bin4'] ?? '')), 0, 4);
        $detectedBrand = sf_card_brand_from_bin4($bin4);
        $brand = $detectedBrand !== '' ? $detectedBrand : sf_text($card['brand'] ?? '', 60);
        $holderInput = sf_text($card['cardholderName'] ?? '', 120);
        $cardholderName = $holderInput !== ''
            ? $holderInput
            : sf_text($existing['cardholder_name'] ?? ($user['name'] ?? ''), 120);
        $expiryInput = sf_card_expiry((string)($card['expiryDate'] ?? ''));
        $expiryDate = $expiryInput !== ''
            ? $expiryInput
            : sf_card_expiry((string)($existing['expiry_date'] ?? ''));
        $productName = sf_text($card['productName'] ?? ($existing['product_name'] ?? ''), 120);
        $now = sf_now();

        if (sf_text($card['nickname'] ?? '', 120) === '') {
            sf_json(['ok' => false, 'message' => 'Informe um apelido para o cartao.'], 422);
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("
            UPDATE cards
                   SET nickname = :nickname,
                       institution = :institution,
                       brand = :brand,
                       bin4 = :bin4,
                       last4 = :last4,
                       product_name = :product_name,
                       cardholder_name = :cardholder_name,
                       expiry_date = :expiry_date,
                       closing_day = :closing_day,
                       due_day = :due_day,
                       limit_total = :limit_total,
                       active = :active,
                       notes = :notes,
                       updated_at = :updated_at
                 WHERE id = :id
                   AND user_id = :user_id
            ");
            $stmt->execute([
                ':id' => $id,
                ':user_id' => $userId,
                ':nickname' => sf_text($card['nickname'] ?? '', 120),
                ':institution' => sf_text($card['institution'] ?? '', 120),
                ':brand' => $brand,
                ':bin4' => $bin4,
                ':last4' => $last4,
                ':product_name' => $productName,
                ':cardholder_name' => $cardholderName,
                ':expiry_date' => $expiryDate,
                ':closing_day' => max(1, min(31, (int)($card['closingDay'] ?? 1))),
                ':due_day' => max(1, min(31, (int)($card['dueDay'] ?? 10))),
                ':limit_total' => abs(sf_money($card['limitTotal'] ?? 0)),
                ':active' => !empty($card['active']) ? 1 : 0,
                ':notes' => sf_text($card['notes'] ?? '', 800),
                ':updated_at' => $now,
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO cards (
                    user_id, nickname, institution, brand, bin4, last4, product_name, cardholder_name, expiry_date, closing_day, due_day,
                    limit_total, active, notes, created_at, updated_at
                ) VALUES (
                    :user_id, :nickname, :institution, :brand, :bin4, :last4, :product_name, :cardholder_name, :expiry_date, :closing_day, :due_day,
                    :limit_total, :active, :notes, :created_at, :updated_at
                )
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':nickname' => sf_text($card['nickname'] ?? '', 120),
                ':institution' => sf_text($card['institution'] ?? '', 120),
                ':brand' => $brand,
                ':bin4' => $bin4,
                ':last4' => $last4,
                ':product_name' => $productName,
                ':cardholder_name' => $cardholderName,
                ':expiry_date' => $expiryDate,
                ':closing_day' => max(1, min(31, (int)($card['closingDay'] ?? 1))),
                ':due_day' => max(1, min(31, (int)($card['dueDay'] ?? 10))),
                ':limit_total' => abs(sf_money($card['limitTotal'] ?? 0)),
                ':active' => !empty($card['active']) ? 1 : 0,
                ':notes' => sf_text($card['notes'] ?? '', 800),
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
        }
        sf_json(sf_dashboard($pdo, $user, $month));
    }

    if ($action === 'delete_card') {
        $cardId = (int)($input['id'] ?? 0);
        $unlink = $pdo->prepare('UPDATE transactions SET card_id = NULL WHERE card_id = :id AND user_id = :user_id');
        $unlink->execute([':id' => $cardId, ':user_id' => $userId]);
        $stmt = $pdo->prepare('DELETE FROM cards WHERE id = :id AND user_id = :user_id');
        $stmt->execute([':id' => $cardId, ':user_id' => $userId]);
        sf_json(sf_dashboard($pdo, $user, $month));
    }

    if ($action === 'save_goal') {
        $goal = is_array($input['goal'] ?? null) ? $input['goal'] : [];
        $id = (int)($goal['id'] ?? 0);
        $referenceMonth = sf_month($goal['referenceMonth'] ?? $month);
        $now = sf_now();
        if (sf_text($goal['name'] ?? '', 160) === '') {
            sf_json(['ok' => false, 'message' => 'Informe o nome da meta.'], 422);
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("
                UPDATE goals
                   SET name = :name,
                       target_amount = :target_amount,
                       saved_amount = :saved_amount,
                       reference_month = :reference_month,
                       active = :active,
                       updated_at = :updated_at
                 WHERE id = :id
                   AND user_id = :user_id
            ");
            $stmt->execute([
                ':id' => $id,
                ':user_id' => $userId,
                ':name' => sf_text($goal['name'] ?? '', 160),
                ':target_amount' => abs(sf_money($goal['targetAmount'] ?? 0)),
                ':saved_amount' => abs(sf_money($goal['savedAmount'] ?? 0)),
                ':reference_month' => $referenceMonth,
                ':active' => !empty($goal['active']) ? 1 : 0,
                ':updated_at' => $now,
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO goals (user_id, name, target_amount, saved_amount, reference_month, active, created_at, updated_at)
                VALUES (:user_id, :name, :target_amount, :saved_amount, :reference_month, :active, :created_at, :updated_at)
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':name' => sf_text($goal['name'] ?? '', 160),
                ':target_amount' => abs(sf_money($goal['targetAmount'] ?? 0)),
                ':saved_amount' => abs(sf_money($goal['savedAmount'] ?? 0)),
                ':reference_month' => $referenceMonth,
                ':active' => !empty($goal['active']) ? 1 : 0,
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
        }
        sf_json(sf_dashboard($pdo, $user, $referenceMonth));
    }

    if ($action === 'delete_goal') {
        $stmt = $pdo->prepare('DELETE FROM goals WHERE id = :id AND user_id = :user_id');
        $stmt->execute([':id' => (int)($input['id'] ?? 0), ':user_id' => $userId]);
        sf_json(sf_dashboard($pdo, $user, $month));
    }

    if ($action === 'import_statement') {
        $text = (string)($input['extractedText'] ?? '');
        $importType = sf_text($input['importType'] ?? 'extrato', 60);
        $fileName = sf_text($input['fileName'] ?? '', 190);
        $preferredPeriod = sf_period($input['periodType'] ?? null);
        $movements = is_array($input['movements'] ?? null)
            ? $input['movements']
            : sf_parse_statement($text, $month, $preferredPeriod);
        $movements = sf_enrich_import_movements($pdo, $userId, $movements);

        if (empty($input['confirm'])) {
            sf_json([
                'ok' => true,
                'message' => count($movements) . ' movimento(s) reconhecido(s). Revise antes de salvar.',
                'movements' => $movements,
            ]);
        }

        $importedMonths = [];
        foreach ($movements as &$movement) {
            if (!is_array($movement)) {
                continue;
            }

            $method = sf_text($movement['paymentMethod'] ?? '', 40);
            if (in_array($method, ['pix', 'debito'], true)
                && !sf_import_movement_is_card($movement)
                && empty($movement['periodTypeConfirmed'])) {
                sf_json([
                    'ok' => false,
                    'message' => 'Confirme o saldo de origem (Dia 20 ou 5o dia util) para cada movimento Pix ou debito antes de salvar.',
                ], 422);
            }

            $movement['movementDate'] = sf_date($movement['movementDate'] ?? null);
            $movement['referenceMonth'] = substr($movement['movementDate'], 0, 7) ?: $month;
            $movement = sf_apply_credit_schedule($movement);
            $importedMonths[] = $movement['referenceMonth'];
        }
        unset($movement);
        $importedMonths = array_values(array_unique($importedMonths));

        $pdo->beginTransaction();
        try {
            $importId = sf_save_import($pdo, $userId, $importType, $fileName, $text, $movements);
            foreach ($movements as $movement) {
                if (!is_array($movement)) {
                    continue;
                }
                $movement['source'] = $importType === 'banco_api' ? 'banco_api' : 'extrato';
                sf_save_transaction_installments($pdo, $userId, $movement, false, $importId);
            }
            $pdo->commit();
        } catch (Throwable $error) {
            $pdo->rollBack();
            throw $error;
        }

        $dashboard = sf_dashboard($pdo, $user, $month);
        $dashboard['importedMonths'] = $importedMonths;
        sf_json($dashboard);
    }

    if ($action === 'bank_sync') {
        $transactions = is_array($input['transactions'] ?? null) ? $input['transactions'] : [];
        if (!$transactions) {
            sf_json([
                'ok' => true,
                'status' => 'prepared_connector',
                'message' => 'Conector bancario pronto. Para sincronizacao real, conecte um provedor Open Finance/Pix e envie as transacoes autorizadas.',
            ]);
        }

        $pdo->beginTransaction();
        try {
            $importId = sf_save_import($pdo, $userId, 'banco_api', 'api-bank-sync', '', $transactions);
            foreach ($transactions as $transaction) {
                if (is_array($transaction)) {
                    $transaction['source'] = 'banco_api';
                    sf_save_transaction_installments($pdo, $userId, $transaction, false, $importId);
                }
            }
            $pdo->commit();
        } catch (Throwable $error) {
            $pdo->rollBack();
            throw $error;
        }
        sf_json(sf_dashboard($pdo, $user, $month));
    }

    sf_json(['ok' => false, 'message' => 'Acao invalida.'], 422);
} catch (Throwable $error) {
    sf_json([
        'ok' => false,
        'message' => 'Falha no financeiro: ' . $error->getMessage(),
    ], 500);
}
