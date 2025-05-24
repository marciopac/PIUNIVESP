<?php
// Verifica se a sessão não está ativa antes de iniciar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pag = 'comparativo_vendas_mensais';

if(@$_SESSION['usuarios'] == 'ocultar'){
    echo "<script>window.location='../index.php'</script>";
    exit();
}

// Lista de conexões para os bancos de dados com nomes das lojas
include $_SERVER['DOCUMENT_ROOT'] . '/PIUNIVESP/conexao_dashboard.php';

$datas1 = [];
$totals1 = [];
$datas2 = [];
$totals2 = [];
$lojas1 = [];
$lojas2 = [];
$totalVendas1 = 0;
$totalVendas2 = 0;

if (isset($_POST['mes1']) && isset($_POST['mes2'])) {
    $mes1 = date('m', strtotime($_POST['mes1'] . '-01'));
    $ano1 = date('Y', strtotime($_POST['mes1'] . '-01'));
    $mes2 = date('m', strtotime($_POST['mes2'] . '-01'));
    $ano2 = date('Y', strtotime($_POST['mes2'] . '-01'));

    $incluirCanceladas = isset($_POST['incluir_canceladas']) ? '' : 'AND VENDAS_STATUS != 9';

    foreach ($databases as $db) {
        try {
            $pdo = new PDO("firebird:dbname=" . $db['path'], $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Consulta para o primeiro mês
            $stmt1 = $pdo->prepare("SELECT EXTRACT(YEAR FROM VENDAS_DATA) AS ANO, 
                                   EXTRACT(MONTH FROM VENDAS_DATA) AS MES, 
                                   SUM(VENDAS_VALORFINAL) AS TOTAL_VENDAS 
                                   FROM VENDAS 
                                   WHERE EXTRACT(MONTH FROM VENDAS_DATA) = :mes 
                                   AND EXTRACT(YEAR FROM VENDAS_DATA) = :ano 
                                   $incluirCanceladas 
                                   GROUP BY ANO, MES 
                                   ORDER BY ANO, MES");
            $stmt1->execute(['mes' => $mes1, 'ano' => $ano1]);

            while ($row1 = $stmt1->fetch(PDO::FETCH_ASSOC)) {
                $datas1[] = date('m/Y', strtotime($row1['ANO'].'-'.$row1['MES'].'-01'));
                $totals1[] = $row1['TOTAL_VENDAS'];
                $lojas1[] = $db['nome'];
                $totalVendas1 += $row1['TOTAL_VENDAS'];
            }

            // Consulta para o segundo mês
            $stmt2 = $pdo->prepare("SELECT EXTRACT(YEAR FROM VENDAS_DATA) AS ANO, 
                                   EXTRACT(MONTH FROM VENDAS_DATA) AS MES, 
                                   SUM(VENDAS_VALORFINAL) AS TOTAL_VENDAS 
                                   FROM VENDAS 
                                   WHERE EXTRACT(MONTH FROM VENDAS_DATA) = :mes 
                                   AND EXTRACT(YEAR FROM VENDAS_DATA) = :ano 
                                   $incluirCanceladas 
                                   GROUP BY ANO, MES 
                                   ORDER BY ANO, MES");
            $stmt2->execute(['mes' => $mes2, 'ano' => $ano2]);

            while ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
                $datas2[] = date('m/Y', strtotime($row2['ANO'].'-'.$row2['MES'].'-01'));
                $totals2[] = $row2['TOTAL_VENDAS'];
                $lojas2[] = $db['nome'];
                $totalVendas2 += $row2['TOTAL_VENDAS'];
            }
        } catch (PDOException $e) {
            echo "Erro ao conectar ao banco de dados: " . $e->getMessage() . "<br>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comparativo de Vendas Mensais</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <link rel="shortcut icon" href="/painel/img/logo-favicon.ico" type="image/x-icon">
    
    <style>
        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
            margin: 20px 0;
        }
        
        .form-container {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .results-container {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .watermark { 
            position: fixed; 
            bottom: 10px; 
            right: 10px; 
            opacity: 0.5; 
            font-size: 22px; 
            color: #555; 
        }
        
        .comparison-result {
            font-weight: bold;
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2 class="text-center mb-4">Comparativo de Vendas Mensais</h2>

                <div class="form-container">
                    <form method="POST" class="form-inline justify-content-center">
                        <div class="form-group mx-sm-3 mb-2">
                            <label for="mes1" class="mr-2">Mês 1:</label>
                            <input type="month" id="mes1" name="mes1" class="form-control" 
                                   value="<?php echo isset($_POST['mes1']) ? $_POST['mes1'] : ''; ?>" required>
                        </div>
                        <div class="form-group mx-sm-3 mb-2">
                            <label for="mes2" class="mr-2">Mês 2:</label>
                            <input type="month" id="mes2" name="mes2" class="form-control" 
                                   value="<?php echo isset($_POST['mes2']) ? $_POST['mes2'] : ''; ?>" required>
                        </div>
                        <div class="form-group mx-sm-3 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="incluir_canceladas" name="incluir_canceladas" <?php echo isset($_POST['incluir_canceladas']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="incluir_canceladas">Incluir Canceladas</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mb-2">Comparar</button>
                    </form>
                </div>

                <div class="chart-container">
                    <canvas id="vendasChart"></canvas>
                </div>

                <?php if(isset($_POST['mes1']) && isset($_POST['mes2'])): ?>
                    <div class="results-container">
                        <h5>Total Mês <?php echo date('m/Y', strtotime($_POST['mes1'].'-01')); ?>: R$ <?php echo number_format($totalVendas1, 2, ',', '.'); ?></h5>
                        <h5>Total Mês <?php echo date('m/Y', strtotime($_POST['mes2'].'-01')); ?>: R$ <?php echo number_format($totalVendas2, 2, ',', '.'); ?></h5>
                        <h5 class="comparison-result">
                            Diferença: R$ <?php echo number_format(abs($totalVendas1 - $totalVendas2), 2, ',', '.'); ?> 
                            (<?php echo $totalVendas1 > $totalVendas2 ? 'Mês 1 maior' : 'Mês 2 maior'; ?>)
                        </h5>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const datas1 = <?php echo json_encode($datas1); ?>;
            const totals1 = <?php echo json_encode($totals1); ?>;
            const lojas1 = <?php echo json_encode($lojas1); ?>;
            const datas2 = <?php echo json_encode($datas2); ?>;
            const totals2 = <?php echo json_encode($totals2); ?>;
            const lojas2 = <?php echo json_encode($lojas2); ?>;

            const ctx = document.getElementById('vendasChart').getContext('2d');
            const vendasChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: lojas1.map((loja, index) => `${loja}`),
                    datasets: [
                        {
                            label: 'Mês ' + (datas1.length > 0 ? datas1[0] : ''),
                            data: totals1,
                            backgroundColor: 'rgba(75, 192, 192, 0.7)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Mês ' + (datas2.length > 0 ? datas2[0] : ''),
                            data: totals2,
                            backgroundColor: 'rgba(255, 99, 132, 0.7)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR');
                                }
                            }
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += 'R$ ' + context.raw.toLocaleString('pt-BR');
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>

    
    <div class="watermark">
        <?php echo $ambiente == 0 ? 'AMBIENTE DE TESTE' : 'AMBIENTE DE PRODUÇÃO'; ?>
    </div>
</body>
</html>