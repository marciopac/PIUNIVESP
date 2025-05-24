<?php
// Verifica se a sessão não está ativa antes de iniciar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pag = 'vendas_anuais_pagina';

if(@$_SESSION['usuarios'] == 'ocultar'){
    echo "<script>window.location='../index.php'</script>";
    exit();
}

// Lista de conexões para os bancos de dados com nomes das lojas
include $_SERVER['DOCUMENT_ROOT'] . '/PIUNIVESP/conexao_dashboard.php';

$anos = [];
$totals = [];
$lojas = [];
$totalVendas = 0;
$considerarCanceladas = isset($_POST['considerarCanceladas']) ? true : false;

if (isset($_POST['ano'])) {
    $ano = $_POST['ano'];

    foreach ($databases as $db) {
        try {
            $pdo = new PDO("firebird:dbname=" . $db['path'], $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = "SELECT EXTRACT(YEAR FROM VENDAS_DATA) AS ANO, SUM(VENDAS_VALORFINAL) AS TOTAL_VENDAS 
                    FROM VENDAS 
                    WHERE EXTRACT(YEAR FROM VENDAS_DATA) = :ano";
            
            if (!$considerarCanceladas) {
                $sql .= " AND VENDAS_STATUS != 9";
            }
            
            $sql .= " GROUP BY ANO ORDER BY ANO";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['ano' => $ano]);

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $anos[] = $row['ANO'];
                $totals[] = $row['TOTAL_VENDAS'];
                $lojas[] = $db['nome'];
                $totalVendas += $row['TOTAL_VENDAS'];
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
    <title>Dashboard de Vendas Anuais</title>
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
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2 class="text-center mb-4">Dashboard de Vendas Anuais</h2>

                <div class="form-container">
                    <form method="POST" class="form-inline justify-content-center">
                        <div class="form-group mx-sm-3 mb-2">
                            <label for="ano" class="sr-only">Selecione o Ano:</label>
                            <input type="number" id="ano" name="ano" class="form-control" 
                                   min="2000" max="<?php echo date('Y'); ?>" 
                                   value="<?php echo isset($_POST['ano']) ? $_POST['ano'] : date('Y'); ?>" required>
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

                <?php if(isset($_POST['ano'])): ?>
                    <div class="results-container">
                        <h5>Total de Vendas em <?php echo $_POST['ano']; ?>: R$ <?php echo number_format($totalVendas, 2, ',', '.'); ?></h5>
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
            const anos = <?php echo json_encode($anos); ?>;
            const totals = <?php echo json_encode($totals); ?>;
            const lojas = <?php echo json_encode($lojas); ?>;
            
            const ctx = document.getElementById('vendasChart').getContext('2d');
            const vendasChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: lojas.map((loja, index) => `${anos[index]} - ${loja}`),
                    datasets: [{
                        label: 'Total de Vendas Anuais',
                        data: totals,
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

    <div class="watermark">
        <?php echo $ambiente == 0 ? 'AMBIENTE DE TESTE' : 'AMBIENTE DE PRODUÇÃO'; ?>
    </div>
</body>
</html>