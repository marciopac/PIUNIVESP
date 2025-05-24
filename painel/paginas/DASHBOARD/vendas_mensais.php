<?php
// Lista de conexões para os bancos de dados com nomes das lojas
include $_SERVER['DOCUMENT_ROOT'] . '/PIUNIVESP/conexao_dashboard.php';


$datas = [];
$totals = [];
$lojas = [];
$totalVendas = 0;
$considerarCanceladas = true; // Considerando que sempre deve incluir vendas canceladas

// Definindo o mês e ano atual, sem a necessidade de preenchimento do formulário
$mes = date('m'); // Mês atual
$ano = date('Y'); // Ano atual

foreach ($databases as $db) {
    try {
        // Conectar ao banco de dados usando PDO
        $pdo = new PDO("firebird:dbname=" . $db['path'], $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Executar uma consulta SQL para buscar vendas mensais do mês e ano atual
        $sql = "SELECT EXTRACT(YEAR FROM VENDAS_DATA) AS ANO, EXTRACT(MONTH FROM VENDAS_DATA) AS MES, SUM(VENDAS_VALORFINAL) AS TOTAL_VENDAS 
                FROM VENDAS 
                WHERE EXTRACT(MONTH FROM VENDAS_DATA) = :mes 
                AND EXTRACT(YEAR FROM VENDAS_DATA) = :ano";
        if (!$considerarCanceladas) {
            $sql .= " AND VENDAS_STATUS != 9";
        }
        $sql .= " GROUP BY ANO, MES ORDER BY ANO, MES";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['mes' => $mes, 'ano' => $ano]);

        // Recuperar e armazenar os resultados
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $datas[] = $row['ANO'] . '-' . str_pad($row['MES'], 2, '0', STR_PAD_LEFT);  // Essa linha pode ser ajustada se quiser alterar o formato
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
    <title>Dashboard de Vendas Mensais</title>
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
            height: 450px; /* altura ajustada */
            width: 100%;
        }
    </style>
    <style> .watermark { position: fixed; bottom: 10px; right: 10px; opacity: 0.5; font-size: 22px; color: #555; } </style>
</head>
<body>
    <div class="container">
        <div class="chart-container">
            <canvas id="vendasChart"></canvas>
        </div>

        <!-- Exibindo o total de vendas do mês atual -->
        <div class="mt-4">
            <h5>Total de Vendas do Mês Atual: R$ <?php echo number_format($totalVendas, 2, ',', '.'); ?></h5>
        </div>
    </div>

    <script>
        const months = <?php echo json_encode($datas); ?>;
        const totals = <?php echo json_encode($totals); ?>;
        const lojas = <?php echo json_encode($lojas); ?>;
        
        const ctx = document.getElementById('vendasChart').getContext('2d');
        const vendasChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: lojas,  // Aqui estamos utilizando apenas os nomes das lojas
                datasets: [{
                    label: 'Total de Vendas Mensais',
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
                maintainAspectRatio: false
            }
        });
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <footer class="text-center mt-4">
        <!-- Aqui pode ser adicionado um rodapé se necessário -->
    </footer>
    
    <!-- Marca d'água -->
    <div class="watermark">
    
    </div>
</body>
</html>
