<?php
// Lista de conexões para os bancos de dados com nomes das lojas
include $_SERVER['DOCUMENT_ROOT'] . '/PIUNIVESP/conexao_dashboard.php';


// Definir a data atual de Brasília automaticamente
$dia = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
$dia = $dia->format('Y-m-d');  // Data no formato 'YYYY-MM-DD'
$mes = date('m');  // Extrai o mês da data atual
$ano = date('Y');  // Extrai o ano da data atual

// Variáveis para armazenar os dados
$valores = [];
$lojas = [];
$totalDia = 0;

$considerarCanceladas = true;

foreach ($databases as $db) {
    try {
        // Conectar ao banco de dados usando PDO
        $pdo = new PDO("firebird:dbname=" . $db['path'], $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Consulta de vendas do dia atual
        $sql = "SELECT SUM(VENDAS_VALORFINAL) AS TOTAL_VENDAS 
                FROM VENDAS 
                WHERE VENDAS_DATA = :dia";
        if (!$considerarCanceladas) {
            $sql .= " AND VENDAS_STATUS != 9";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['dia' => $dia]);

        // Armazenar os resultados da consulta de vendas diárias
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['TOTAL_VENDAS'] !== null) {
            $valores[] = $row['TOTAL_VENDAS'];
            $lojas[] = $db['nome']; // Nome da loja
            $totalDia += $row['TOTAL_VENDAS']; // Soma as vendas do dia
        }

    } catch (PDOException $e) {
        echo "Erro ao conectar ao banco de dados: " . $e->getMessage() . "<br>";
    }
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard de Vendas Diária</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="shortcut icon" href="img/logo-favicon.ico" type="image/x-icon">
    <style>
        body {
            padding-top: 20px;
        }
        .container {
            max-width: 600px;
        }
        .chart-container {
            position: relative;
            height: 450px; /* Ajuste na altura */
            width: 100%;
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
    <div class="container">
        

        <!-- Gráfico -->
        <div class="chart-container">
            <canvas id="vendasChart"></canvas>
        </div>

        <!-- Exibição do total de vendas do dia -->
        <div class="mt-4">
            <h5>Total de Vendas do Dia: R$ <?php echo number_format($totalDia, 2, ',', '.'); ?></h5>
        </div>
    </div>

    <script>
        // Dados para o gráfico
        const valores = <?php echo json_encode($valores); ?>;
        const lojas = <?php echo json_encode($lojas); ?>;
        
        const ctx = document.getElementById('vendasChart').getContext('2d');
        const vendasChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: lojas, // Exibir apenas os nomes das lojas
                datasets: [{
                    label: 'Total de Vendas por Loja',
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
    </script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
