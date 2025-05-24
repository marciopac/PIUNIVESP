<?php
$pag = 'vendas_diarias_pagina';

if(@$_SESSION['usuarios'] == 'ocultar'){
    echo "<script>window.location='../index.php'</script>";
    exit();
}

include $_SERVER['DOCUMENT_ROOT'] . '/PIUNIVESP/conexao_dashboard.php';

$datas = [];
$valores = [];
$lojas = [];
$totalDia = 0;
$totalMes = 0;

$dia = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
$dia = $dia->format('Y-m-d');
$mes = date('m');
$ano = date('Y');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dia = $_POST['dia'];
    $considerarCanceladas = isset($_POST['considerarCanceladas']) ? true : false;
} else {
    $considerarCanceladas = false;
}

foreach ($databases as $db) {
    try {
        $pdo = new PDO("firebird:dbname=" . $db['path'], $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "SELECT VENDAS_DATA, SUM(VENDAS_VALORFINAL) AS TOTAL_VENDAS FROM VENDAS WHERE VENDAS_DATA = :dia";
        if (!$considerarCanceladas) {
            $sql .= " AND VENDAS_STATUS != 9";
        }
        $sql .= " GROUP BY VENDAS_DATA ORDER BY VENDAS_DATA";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['dia' => $dia]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $datas[] = date('d/m/Y', strtotime($row['VENDAS_DATA']));
            $valores[] = $row['TOTAL_VENDAS'];
            $lojas[] = $db['nome'];
            $totalDia += $row['TOTAL_VENDAS'];
        }

        $sqlMes = "SELECT SUM(VENDAS_VALORFINAL) AS TOTAL_VENDAS_MES FROM VENDAS WHERE EXTRACT(MONTH FROM VENDAS_DATA) = :mes AND EXTRACT(YEAR FROM VENDAS_DATA) = :ano";
        if (!$considerarCanceladas) {
            $sqlMes .= " AND VENDAS_STATUS != 9";
        }
        $stmtMes = $pdo->prepare($sqlMes);
        $stmtMes->execute(['mes' => $mes, 'ano' => $ano]);
        $rowMes = $stmtMes->fetch(PDO::FETCH_ASSOC);
        $totalMes += $rowMes['TOTAL_VENDAS_MES'];

    } catch (PDOException $e) {
        echo "Erro ao conectar ao banco de dados: " . $e->getMessage() . "<br>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Vendas Diária</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <link rel="shortcut icon" href="/painel/img/logo-favicon.ico" type="image/x-icon">
    
    <style>
        .chart-container {
            position: relative;
            height: 300px;
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
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2 class="text-center mb-4">Dashboard de Vendas Diárias - <?php echo date('d/m/Y', strtotime($dia)); ?></h2>

                <div class="form-container">
                    <form method="POST" class="form-inline justify-content-center">
                        <div class="form-group mx-sm-3 mb-2">
                            <label for="dia" class="sr-only">Selecione o Dia:</label>
                            <input type="date" id="dia" name="dia" class="form-control" value="<?php echo $dia; ?>" required>
                        </div>
                        <div class="form-group mx-sm-3 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="considerarCanceladas" name="considerarCanceladas" <?php echo $considerarCanceladas ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="considerarCanceladas">Incluir Vendas Canceladas</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mb-2">Enviar</button>
                    </form>
                </div>

                <div class="chart-container">
                    <canvas id="vendasChart"></canvas>
                </div>

                <div class="results-container">
                    <h5>Total de Vendas do Dia: R$ <?php echo number_format($totalDia, 2, ',', '.'); ?></h5>
                    <h5>Total de Vendas do Mês Atual: R$ <?php echo number_format($totalMes, 2, ',', '.'); ?></h5>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const valores = <?php echo json_encode($valores); ?>;
            const lojas = <?php echo json_encode($lojas); ?>;
            
            const ctx = document.getElementById('vendasChart').getContext('2d');
            const vendasChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: lojas,
                    datasets: [{
                        label: 'Total de Vendas por Dia',
                        data: valores,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
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
</body>
</html>