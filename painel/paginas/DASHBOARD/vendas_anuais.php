<?php
// Lista de conexões para os bancos de dados com nomes das lojas
include $_SERVER['DOCUMENT_ROOT'] . '/PIUNIVESP/conexao_dashboard.php';

// Variável para definir o ambiente (0 = Teste, 1 = Produção)
$ambiente = 0;

$anos = [];
$totals = [];
$lojas = [];
$totalVendas = 0;

// Definindo o ano atual automaticamente
$anoAtual = date('Y');
$considerarCanceladas = false; // Filtro de vendas canceladas será sempre falso agora (não há checkbox)

foreach ($databases as $db) {
    try {
        // Conectar ao banco de dados usando PDO
        $pdo = new PDO("firebird:dbname=" . $db['path'], $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Executar uma consulta SQL para buscar vendas anuais do ano atual
        $sql = "SELECT EXTRACT(YEAR FROM VENDAS_DATA) AS ANO, SUM(VENDAS_VALORFINAL) AS TOTAL_VENDAS 
                FROM VENDAS 
                WHERE EXTRACT(YEAR FROM VENDAS_DATA) = :ano";
        if (!$considerarCanceladas) {
            $sql .= " AND VENDAS_STATUS != 9"; // Ignora vendas canceladas
        }
        $sql .= " GROUP BY ANO ORDER BY ANO";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['ano' => $anoAtual]);

        // Recuperar e armazenar os resultados
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $anos[] = $row['ANO'];
            $totals[] = $row['TOTAL_VENDAS'];
            $lojas[] = $db['nome']; // Adiciona o nome da loja
            $totalVendas += $row['TOTAL_VENDAS']; // Adiciona ao total geral de vendas
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
    <title>Dashboard de Vendas Anuais</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="shortcut icon" href="img/logo-favicon.ico" type="image/x-icon">
    <style>
        body {
            padding-top: 20px;
        }
        .container {
            max-width: 800px;
        }
        .chart-container {
            position: relative;
            height: 450px; /* altura ajustada */
            width: 100%;
        }
        .hidden {
            display: none;
        }
    </style>
    <style> .watermark { position: fixed; bottom: 10px; right: 10px; opacity: 0.5; font-size: 22px; color: #555; } </style>
</head>
<body>
    <div class="container">
        <div class="chart-container">
            <canvas id="vendasChart"></canvas>
        </div>

        <div class="mt-4">
            <h5>Total de Vendas do Ano Atual: <?php echo number_format($totalVendas, 2, ',', '.'); ?></h5>
        </div>
    </div>

    <script>
        const anos = <?php echo json_encode($anos); ?>;
        const totals = <?php echo json_encode($totals); ?>;
        const lojas = <?php echo json_encode($lojas); ?>;
        
        const ctx = document.getElementById('vendasChart').getContext('2d');
        const vendasChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: lojas, // Apenas nomes das lojas
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
                        beginAtZero: true
                    },
                    x: {
                        ticks: {
                            autoSkip: false, // Impede que os rótulos sejam omitidos
                            maxRotation: 90, // Rotaciona os rótulos para melhor visualização
                            minRotation: 90 // Mantém a rotação consistente
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true, // Exibe a legenda
                        position: 'top', // Posiciona a legenda no topo
                        labels: {
                            font: {
                                size: 12 // Tamanho da fonte da legenda
                            }
                        }
                    }
                },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.amazonaws.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>