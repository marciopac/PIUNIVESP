<?php
// Verifica se a sessão não está ativa antes de iniciar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pag = 'comparativo_vendas_anuais';

if(@$_SESSION['usuarios'] == 'ocultar'){
    echo "<script>window.location='../index.php'</script>";
    exit();
}

// Lista de conexões para os bancos de dados com nomes das lojas
include $_SERVER['DOCUMENT_ROOT'] . '/PIUNIVESP/conexao_dashboard.php';

$totals1 = [];
$totals2 = [];
$lojas1 = [];
$lojas2 = [];
$totalVendas1 = 0;
$totalVendas2 = 0;
$ano1 = '';
$ano2 = '';

if (isset($_POST['ano1']) && isset($_POST['ano2'])) {
    $ano1 = $_POST['ano1'];
    $ano2 = $_POST['ano2'];

    $incluirCanceladas = isset($_POST['incluir_canceladas']) ? '' : 'AND VENDAS_STATUS != 9';

    foreach ($databases as $db) {
        try {
            $pdo = new PDO("firebird:dbname=" . $db['path'], $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Consulta para o primeiro ano
            $stmt1 = $pdo->prepare("SELECT SUM(VENDAS_VALORFINAL) AS TOTAL_VENDAS 
                                   FROM VENDAS 
                                   WHERE EXTRACT(YEAR FROM VENDAS_DATA) = :ano 
                                   $incluirCanceladas");
            $stmt1->execute(['ano' => $ano1]);

            if ($row1 = $stmt1->fetch(PDO::FETCH_ASSOC)) {
                $totals1[] = $row1['TOTAL_VENDAS'] ?? 0;
                $lojas1[] = $db['nome'];
                $totalVendas1 += $row1['TOTAL_VENDAS'] ?? 0;
            }

            // Consulta para o segundo ano
            $stmt2 = $pdo->prepare("SELECT SUM(VENDAS_VALORFINAL) AS TOTAL_VENDAS 
                                   FROM VENDAS 
                                   WHERE EXTRACT(YEAR FROM VENDAS_DATA) = :ano 
                                   $incluirCanceladas");
            $stmt2->execute(['ano' => $ano2]);

            if ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
                $totals2[] = $row2['TOTAL_VENDAS'] ?? 0;
                $lojas2[] = $db['nome'];
                $totalVendas2 += $row2['TOTAL_VENDAS'] ?? 0;
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
    <title>Comparativo de Vendas Anuais</title>
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
            font-size: 1.2rem;
        }
        
        .form-group label {
            margin-right: 10px;
            min-width: 80px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2 class="text-center mb-4">Comparativo de Vendas Anuais</h2>

                <div class="form-container">
                    <form method="POST" class="form-inline justify-content-center">
                        <div class="form-group mx-sm-3 mb-2">
                            <label for="ano1">Ano 1:</label>
                            <input type="number" id="ano1" name="ano1" class="form-control" 
                                   min="2000" max="<?php echo date('Y'); ?>" 
                                   value="<?php echo isset($_POST['ano1']) ? htmlspecialchars($_POST['ano1'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                        </div>
                        <div class="form-group mx-sm-3 mb-2">
                            <label for="ano2">Ano 2:</label>
                            <input type="number" id="ano2" name="ano2" class="form-control" 
                                   min="2000" max="<?php echo date('Y'); ?>" 
                                   value="<?php echo isset($_POST['ano2']) ? htmlspecialchars($_POST['ano2'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>
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

                <?php if(isset($_POST['ano1']) && isset($_POST['ano2'])): ?>
                    <div class="results-container">
                        <h5>Total <?php echo htmlspecialchars($ano1, ENT_QUOTES, 'UTF-8'); ?>: R$ <?php echo number_format($totalVendas1, 2, ',', '.'); ?></h5>
                        <h5>Total <?php echo htmlspecialchars($ano2, ENT_QUOTES, 'UTF-8'); ?>: R$ <?php echo number_format($totalVendas2, 2, ',', '.'); ?></h5>
                        <h5 class="comparison-result">
                            Diferença: R$ <?php echo number_format(abs($totalVendas1 - $totalVendas2), 2, ',', '.'); ?> 
                            (<?php echo $totalVendas1 > $totalVendas2 ? htmlspecialchars($ano1, ENT_QUOTES, 'UTF-8').' maior' : htmlspecialchars($ano2, ENT_QUOTES, 'UTF-8').' maior'; ?>)
                        </h5>
                        <?php if($totalVendas1 > 0 && $totalVendas2 > 0): ?>
                        <h5 class="comparison-result">
                            Variação: <?php echo number_format((($totalVendas2 - $totalVendas1)/$totalVendas1)*100, 2, ',', '.'); ?>%
                        </h5>
                        <?php endif; ?>
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
            const totals1 = <?php echo json_encode($totals1); ?>;
            const lojas1 = <?php echo json_encode($lojas1); ?>;
            const totals2 = <?php echo json_encode($totals2); ?>;
            const lojas2 = <?php echo json_encode($lojas2); ?>;
            const ano1 = <?php echo isset($_POST['ano1']) ? json_encode($_POST['ano1']) : '""'; ?>;
            const ano2 = <?php echo isset($_POST['ano2']) ? json_encode($_POST['ano2']) : '""'; ?>;
            
            const ctx = document.getElementById('vendasChart').getContext('2d');
            const vendasChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: lojas1,
                    datasets: [
                        {
                            label: 'Ano ' + ano1,
                            data: totals1,
                            backgroundColor: 'rgba(75, 192, 192, 0.7)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Ano ' + ano2,
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