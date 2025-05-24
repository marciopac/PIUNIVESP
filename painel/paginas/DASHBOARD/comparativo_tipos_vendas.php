<?php
// Lista de conexões para os bancos de dados com nomes das lojas
$databases = [
    ['path' => '26.73.82.46:C:\\DinizSoft\\gerencial3\\gerencial3.fdb', 'nome' => 'PACAEMBU'],
    ['path' => '26.128.255.146:C:\\DinizSoft\\gerencial3\\pacaembubig.fdb', 'nome' => 'BIG-PACAEMBU'],
    ['path' => '26.221.152.160:C:\\DinizSoft\\gerencial3\\gerencial3.fdb', 'nome' => 'IRAPURU'],
    ['path' => '26.83.11.24:C:\\DinizSoft\\gerencial3\\Junqueiropolis.fdb', 'nome' => 'JUNQUEIROPOLIS'],
    ['path' => '26.238.22.238:C:\\DinizSoft\\gerencial3\\Gerencial3.fdb', 'nome' => 'BIG-JUNQUEIRA'], 
    ['path' => '26.239.157.135:C:\\DinizSoft\\gerencial3\\TUPIPAULISTA.fdb', 'nome' => 'TUPI PAULISTA'],
    ['path' => '26.187.238.56:C:\\DinizSoft\\gerencial3\\FloridaPaulista.fdb', 'nome' => 'FLORIDA PAULISTA'],
    ['path' => '26.116.153.141:C:\\DinizSoft\\gerencial3\\Adamantina.fdb', 'nome' => 'ADAMANTINA'],  
    //['path' => '26.48.201.120:C:\\DinizSoft\\Gerencial3\\Gerencial3.fdb', 'nome' => 'BIG-OCZ'], 
];

$username = 'SYSDBA';
$password = 'masterkey';

// Variável para definir o ambiente (0 = Teste, 1 = Produção)
$ambiente = 1;

// Resultados da pesquisa e meses do ano
$searchResults = [];
$meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

if (isset($_GET['year']) && isset($_GET['month']) && isset($_GET['day'])) {
    $year = intval($_GET['year']);
    $month = intval($_GET['month']);
    $day = intval($_GET['day']);

    // Validação básica de ano, mês e dia
    if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12 || $day < 1 || $day > 31) {
        echo "<script>alert('Data inválida!');</script>";
    } else {
        foreach ($databases as $db) {
            try {
                // Conectar ao banco de dados usando PDO
                $pdo = new PDO("firebird:dbname=" . $db['path'], $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Ajustar a consulta SQL com base no ano, mês e dia
                $sql = "SELECT c.condpagto_descricao, COUNT(v.vendas_id) AS quantidade_de_vendas
                        FROM vendas v
                        JOIN condpagto c ON v.vendas_idcondicao = c.condpagto_id
                        WHERE EXTRACT(YEAR FROM v.vendas_data) = :year 
                        AND EXTRACT(MONTH FROM v.vendas_data) = :month
                        AND EXTRACT(DAY FROM v.vendas_data) = :day
                        GROUP BY c.condpagto_descricao";

                $stmt = $pdo->prepare($sql);
                $stmt->execute(['year' => $year, 'month' => $month, 'day' => $day]);

                // Recuperar e armazenar os resultados
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (isset($row['condpagto_descricao']) && isset($row['quantidade_de_vendas'])) {
                        $condicao = $row['condpagto_descricao'];
                        $quantidade = $row['quantidade_de_vendas'];

                        // Inicializa o array de condição para cada loja se não existir
                        if (!isset($searchResults[$db['nome']])) {
                            $searchResults[$db['nome']] = [];
                        }
                        $searchResults[$db['nome']][$condicao] = $quantidade;
                    }
                }

            } catch (PDOException $e) {
                echo "<div class='alert alert-danger'>Erro ao conectar ao banco de dados: " . $e->getMessage() . "</div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Vendas</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="shortcut icon" href="img/logo-favicon.ico" type="image/x-icon">
    <style>
        .table th, .table td {
            white-space: nowrap;
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
        <h2 class="text-center">Relatório de Vendas</h2>
        <form method="GET" class="form-inline justify-content-center mb-3">
            <div class="form-group mx-sm-3 mb-2">
                <label for="year" class="sr-only">Ano:</label>
                <input type="number" id="year" name="year" class="form-control" placeholder="Ano" required>
            </div>
            <div class="form-group mx-sm-3 mb-2">
                <label for="month" class="sr-only">Mês:</label>
                <select id="month" name="month" class="form-control" required>
                    <?php foreach ($meses as $index => $mes): ?>
                        <option value="<?php echo $index + 1; ?>"><?php echo $mes; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mx-sm-3 mb-2">
                <label for="day" class="sr-only">Dia:</label>
                <input type="number" id="day" name="day" class="form-control" placeholder="Dia" required>
            </div>
            <button type="submit" class="btn btn-primary mb-2">Filtrar</button>
        </form>

        <?php if ($searchResults): ?>
            <?php foreach ($searchResults as $store => $data): ?>
                <h3><?php echo $store; ?></h3>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Condição de Pagamento</th>
                            <th>Quantidade de Vendas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $condicao => $quantidade): ?>
                            <tr>
                                <td><?php echo $condicao; ?></td>
                                <td><?php echo $quantidade; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <canvas id="vendasChart-<?php echo $store; ?>" width="400" height="200"></canvas>
                <script>
                    const vendasData<?php echo $store; ?> = <?php echo json_encode($data); ?>;
                    const ctx<?php echo $store; ?> = document.getElementById('vendasChart-<?php echo $store; ?>').getContext('2d');
                    
                    const data<?php echo $store; ?> = {
                        labels: Object.keys(vendasData<?php echo $store; ?>),
                        datasets: [{
                            label: 'Vendas',
                            data: Object.values(vendasData<?php echo $store; ?>),
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }]
                    };
                    
                    new Chart(ctx<?php echo $store; ?>, {
                        type: 'bar',
                        data: data<?php echo $store; ?>,
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                </script>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info mt-4">Nenhuma venda encontrada para o período selecionado.</div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.amazonaws.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <footer class="text-center mt-4">
        <p>MS Informatica - (18) 99773-4770</p>
    </footer>

    <div class="watermark">
        <?php echo $ambiente == 0 ? 'AMBIENTE DE TESTE' : 'AMBIENTE DE PRODUÇÃO'; ?>
    </div>
</body>
</html>