<?php
// Configuração de conexão
$host = 'localhost:C:\\DinizSoft\\Dados\\gerencial3.fdb';
$username = 'SYSDBA';
$password = 'masterkey';

$datas = [];
$valores = [];

if (isset($_POST['dia'])) {
    $dia = $_POST['dia'];

    try {
        // Conectar ao banco de dados usando PDO
        $pdo = new PDO("firebird:dbname=$host", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "Conexão com o banco de dados realizada com sucesso!<br>";

        // Executar uma consulta SQL para buscar vendas_data e vendas_valortotal filtrado pelo dia selecionado
        $stmt = $pdo->prepare("SELECT VENDAS_DATA, SUM(VENDAS_VALORTOTAL) AS TOTAL_VENDAS FROM VENDAS WHERE VENDAS_DATA = :dia GROUP BY VENDAS_DATA ORDER BY VENDAS_DATA");
        $stmt->execute(['dia' => $dia]);

        // Recuperar e armazenar os resultados
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $datas[] = $row['VENDAS_DATA'];
            $valores[] = $row['TOTAL_VENDAS'];
        }
    } catch (PDOException $e) {
        echo "Erro ao conectar ao banco de dados: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard de Vendas</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h2>Dashboard de Vendas</h2>
    <form method="POST">
        <label for="dia">Selecione o Dia:</label>
        <input type="date" id="dia" name="dia">
        <button type="submit">Enviar</button>
    </form>

    <canvas id="vendasChart" width="400" height="200"></canvas>

    <script>
        const datas = <?php echo json_encode($datas); ?>;
        const valores = <?php echo json_encode($valores); ?>;
        
        const ctx = document.getElementById('vendasChart').getContext('2d');
        const vendasChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: datas,
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
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
