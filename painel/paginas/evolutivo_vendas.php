<?php
// Verifica se a sessão não está ativa antes de iniciar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pag = 'evolutivo_vendas';

if(@$_SESSION['usuarios'] == 'ocultar'){
    echo "<script>window.location='../index.php'</script>";
    exit();
}

// Lista de conexões para os bancos de dados com nomes das lojas
include $_SERVER['DOCUMENT_ROOT'] . '/PIUNIVESP/conexao_dashboard.php';

$anoAtual = date('Y');
$anosSelecionados = isset($_POST['anos']) ? (int)$_POST['anos'] : 3; // Padrão de 3 anos
$lojaSelecionada = isset($_POST['loja']) ? $_POST['loja'] : 'PACAEMBU';
$considerarAnoAtual = isset($_POST['considerar_ano_atual']) ? true : false;

$dadosVendas = [];
$labelsMeses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dec'];

// Conectar ao banco de dados da loja selecionada
foreach ($databases as $db) {
    if ($db['nome'] == $lojaSelecionada) {
        try {
            $pdo = new PDO("firebird:dbname=" . $db['path'], $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Definir o intervalo de anos
            $anoFim = $considerarAnoAtual ? $anoAtual : $anoAtual - 1;
            $anoInicio = $anoFim - $anosSelecionados + 1;

            // Consultar vendas por mês para o número de anos selecionados
            $sql = "
                SELECT 
                    EXTRACT(YEAR FROM VENDAS_DATA) AS ANO,
                    EXTRACT(MONTH FROM VENDAS_DATA) AS MES,
                    SUM(VENDAS_VALORFINAL) AS TOTAL_VENDAS
                FROM VENDAS
                WHERE EXTRACT(YEAR FROM VENDAS_DATA) BETWEEN :anoInicio AND :anoFim
                AND VENDAS_STATUS != 9
                GROUP BY ANO, MES
                ORDER BY ANO, MES;
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute(['anoInicio' => $anoInicio, 'anoFim' => $anoFim]);

            // Organizar os dados para o gráfico
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $ano = $row['ANO'];
                $mes = $row['MES'];
                $totalVendas = $row['TOTAL_VENDAS'];

                if (!isset($dadosVendas[$ano])) {
                    $dadosVendas[$ano] = array_fill(1, 12, 0);
                }
                $dadosVendas[$ano][$mes] = $totalVendas;
            }
        } catch (PDOException $e) {
            echo "Erro ao conectar ao banco de dados: " . $e->getMessage() . "<br>";
        }
    }
}

// Calcular estatísticas
$totaisPorAno = [];
$melhorMesPorAno = [];
$piorMesPorAno = [];
$crescimentoAnual = [];

$anosOrdenados = !empty($dadosVendas) ? array_keys($dadosVendas) : [];
sort($anosOrdenados);

foreach ($dadosVendas as $ano => $vendasMensais) {
    $totaisPorAno[$ano] = array_sum($vendasMensais);
    
    // Verifica se há vendas antes de calcular melhor/pior mês
    if (!empty($vendasMensais) && max($vendasMensais) > 0) {
        $melhorMesPorAno[$ano] = [
            'mes' => array_search(max($vendasMensais), $vendasMensais),
            'valor' => max($vendasMensais)
        ];
        $piorMesPorAno[$ano] = [
            'mes' => array_search(min($vendasMensais), $vendasMensais),
            'valor' => min($vendasMensais)
        ];
    } else {
        $melhorMesPorAno[$ano] = ['mes' => null, 'valor' => 0];
        $piorMesPorAno[$ano] = ['mes' => null, 'valor' => 0];
    }
}

// Calcular crescimento anual - só se tivermos pelo menos 2 anos
for ($i = 1; $i < count($anosOrdenados); $i++) {
    $anoAtual = $anosOrdenados[$i];
    $anoAnterior = $anosOrdenados[$i-1];
    $crescimento = 0;
    
    if (isset($totaisPorAno[$anoAnterior]) && $totaisPorAno[$anoAnterior] > 0) {
        $crescimento = (($totaisPorAno[$anoAtual] - $totaisPorAno[$anoAnterior]) / $totaisPorAno[$anoAnterior]) * 100;
    }
    
    $crescimentoAnual[$anoAtual] = $crescimento;
}

// Melhor e pior ano - só se tivermos dados
$melhorAno = !empty($totaisPorAno) ? array_search(max($totaisPorAno), $totaisPorAno) : null;
$piorAno = !empty($totaisPorAno) ? array_search(min($totaisPorAno), $totaisPorAno) : null;

// Calcular média de crescimento
$mediaCrescimento = 0;
if (!empty($crescimentoAnual)) {
    $mediaCrescimento = array_sum($crescimentoAnual) / count($crescimentoAnual);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evolutivo de Vendas - <?php echo htmlspecialchars($lojaSelecionada, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <link rel="shortcut icon" href="/painel/img/logo-favicon.ico" type="image/x-icon">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 25px;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 30px;
        }
        .card {
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .card-header {
            font-weight: bold;
            background-color: #f1f1f1;
        }
        .stat-card {
            border-left: 4px solid #4e73df;
        }
        .positive-growth {
            color: #28a745;
        }
        .negative-growth {
            color: #dc3545;
        }
        .form-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .watermark {
            position: fixed;
            bottom: 10px;
            right: 10px;
            opacity: 0.5;
            font-size: 14px;
            color: #555;
        }
        .no-data {
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4 text-center">Análise Evolutiva de Vendas</h2>
        
        <!-- Formulário de Seleção -->
        <div class="form-section">
            <form method="POST">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="loja">Loja</label>
                        <select class="form-control" id="loja" name="loja">
                            <?php foreach ($databases as $db): ?>
                                <option value="<?php echo htmlspecialchars($db['nome'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($db['nome'] == $lojaSelecionada) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($db['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="anos">Período (anos)</label>
                        <select class="form-control" id="anos" name="anos">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($anosSelecionados == $i) ? 'selected' : ''; ?>>
                                    <?php echo $i; ?> Ano<?php echo ($i > 1) ? 's' : ''; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <div class="form-check mt-4 pt-2">
                            <input class="form-check-input" type="checkbox" id="considerar_ano_atual" name="considerar_ano_atual" <?php echo $considerarAnoAtual ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="considerar_ano_atual">Incluir ano atual</label>
                        </div>
                    </div>
                    <div class="form-group col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-block">Atualizar</button>
                    </div>
                </div>
            </form>
        </div>

        <?php if (empty($anosOrdenados)): ?>
            <div class="alert alert-warning">
                Nenhum dado encontrado para os critérios selecionados. Por favor, ajuste os filtros e tente novamente.
            </div>
        <?php else: ?>
            <!-- Gráfico de Vendas Mensais -->
            <div class="card">
                <div class="card-header">
                    Evolução Mensal das Vendas (R$)
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="vendasChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Estatísticas -->
            <div class="row">
                <!-- Resumo por Ano -->
                <div class="col-md-6">
                    <div class="card stat-card">
                        <div class="card-header">
                            Resumo por Ano
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Ano</th>
                                            <th>Total (R$)</th>
                                            <th>Crescimento</th>
                                            <th>Melhor Mês</th>
                                            <th>Pior Mês</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($anosOrdenados as $ano): ?>
                                        <tr>
                                            <td><?php echo $ano; ?></td>
                                            <td><?php echo number_format($totaisPorAno[$ano], 2, ',', '.'); ?></td>
                                            <td class="<?php echo isset($crescimentoAnual[$ano]) ? ($crescimentoAnual[$ano] >= 0 ? 'positive-growth' : 'negative-growth') : ''; ?>">
                                                <?php if(isset($crescimentoAnual[$ano])): ?>
                                                    <?php echo number_format($crescimentoAnual[$ano], 2, ',', '.'); ?>%
                                                <?php else: ?>
                                                    <span class="no-data">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($melhorMesPorAno[$ano]['mes'] !== null): ?>
                                                    <?php echo $labelsMeses[$melhorMesPorAno[$ano]['mes'] - 1]; ?><br>
                                                    <small><?php echo number_format($melhorMesPorAno[$ano]['valor'], 2, ',', '.'); ?></small>
                                                <?php else: ?>
                                                    <span class="no-data">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($piorMesPorAno[$ano]['mes'] !== null): ?>
                                                    <?php echo $labelsMeses[$piorMesPorAno[$ano]['mes'] - 1]; ?><br>
                                                    <small><?php echo number_format($piorMesPorAno[$ano]['valor'], 2, ',', '.'); ?></small>
                                                <?php else: ?>
                                                    <span class="no-data">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Destaques -->
                <div class="col-md-6">
                    <div class="card stat-card">
                        <div class="card-header">
                            Destaques
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h5>Melhor Ano</h5>
                                <?php if ($melhorAno !== null): ?>
                                    <p class="lead positive-growth">
                                        <?php echo $melhorAno; ?> - R$ <?php echo number_format($totaisPorAno[$melhorAno], 2, ',', '.'); ?>
                                    </p>
                                <?php else: ?>
                                    <p class="lead no-data">Nenhum dado disponível</p>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <h5>Pior Ano</h5>
                                <?php if ($piorAno !== null): ?>
                                    <p class="lead negative-growth">
                                        <?php echo $piorAno; ?> - R$ <?php echo number_format($totaisPorAno[$piorAno], 2, ',', '.'); ?>
                                    </p>
                                <?php else: ?>
                                    <p class="lead no-data">Nenhum dado disponível</p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h5>Crescimento Médio Anual</h5>
                                <p class="lead">
                                    <?php if (!empty($crescimentoAnual)): ?>
                                        <?php $class = $mediaCrescimento >= 0 ? 'positive-growth' : 'negative-growth'; ?>
                                        <span class="<?php echo $class; ?>">
                                            <?php echo number_format($mediaCrescimento, 2, ',', '.'); ?>%
                                        </span>
                                    <?php else: ?>
                                        <span class="no-data">N/A</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                // Dados para os gráficos
                const dadosVendas = <?php echo json_encode($dadosVendas); ?>;
                const labelsMeses = <?php echo json_encode($labelsMeses); ?>;
                const anosOrdenados = <?php echo json_encode($anosOrdenados); ?>;
                
                // Cores para os anos no gráfico
                const cores = [
                    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                    '#858796', '#5a5c69', '#3a3b45', '#2e2f3a', '#1a1c23'
                ];

                // Preparar dados para o gráfico
                const datasets = anosOrdenados.map((ano, index) => ({
                    label: ano,
                    data: Object.values(dadosVendas[ano]),
                    borderColor: cores[index % cores.length],
                    backgroundColor: cores[index % cores.length] + '20',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }));

                // Gráfico de Vendas Mensais
                const ctxLinhas = document.getElementById('vendasChart').getContext('2d');
                const vendasChart = new Chart(ctxLinhas, {
                    type: 'line',
                    data: {
                        labels: labelsMeses,
                        datasets: datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    boxWidth: 12,
                                    padding: 20
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            label += 'R$ ' + context.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'R$ ' + value.toLocaleString('pt-BR');
                                    }
                                },
                                grid: {
                                    drawBorder: false
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            </script>
        <?php endif; ?>
    </div>

    <div class="watermark">
        <?php echo $ambiente == 0 ? 'AMBIENTE DE TESTE' : 'AMBIENTE DE PRODUÇÃO'; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>