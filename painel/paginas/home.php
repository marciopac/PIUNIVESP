<?php
// Verificação para redirecionar, caso a variável $home seja 'ocultar'
if (isset($home) && $home == 'ocultar') {
    echo "<script>window.location.href='../index.php';</script>";
    exit();
}

// Incluindo conexão com o banco de dados
$conexao_path = $_SERVER['DOCUMENT_ROOT'] . '/PIUNIVESP/conexao_dashboard.php';

if (file_exists($conexao_path)) {
    include $conexao_path;
} else {
    echo "Erro: Arquivo de conexão não encontrado!";
    exit();
}
?>

<div class="main-page margin-mobile">
    <?php if (empty($ativo_sistema)) { ?>
        
    <?php } ?>
</div>

<main class="content" id="mainContent">
    <div class="container">
        <!-- Linha Horizontal com Gráficos lado a lado -->
        <div class="row d-flex justify-content-start">
            <div class="col-md-6">
                <h3>Vendas do Dia</h3>
                <iframe src="/PIUNIVESP/PAINEL/PAGINAS/DASHBOARD/vendas_diarias.php" width="100%" height="600"></iframe>
            </div>
            <div class="col-md-6">
                <h3>Vendas do Mês</h3>
                <iframe src="/PIUNIVESP/PAINEL/PAGINAS/DASHBOARD/vendas_mensais.php" width="100%" height="600"></iframe>
            </div>
            <div class="col-md-12">
                <h3>Vendas do Ano</h3>
                <iframe src="/PIUNIVESP/PAINEL/PAGINAS/DASHBOARD/vendas_anuais.php" width="100%" height="600"></iframe>
            </div>
        </div>
    </div>
</main>