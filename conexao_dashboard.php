<?php
// Lista de conexões para os bancos de dados com nomes das lojas
$databases = [
    ['path' => 'localhost:C:\\DinizSoft\\PACAEMBU\\gerencial3.fdb', 'nome' => 'LOJA 1'],
    ['path' => 'localhost:C:\\DinizSoft\\BIGPACAEMBU\\pacaembubig.fdb', 'nome' => 'LOJA 2'],
    ['path' => 'localhost:C:\\DinizSoft\\IRAPURU\\gerencial3.fdb', 'nome' => 'LOJA 3'],
    ['path' => 'localhost:C:\\DinizSoft\\JUNQUEIROPOLIS\\Junqueiropolis.fdb', 'nome' => 'LOJA 4'],
    ['path' => 'localhost:C:\\DinizSoft\\BIGJUNQUEIRA\\Gerencial3.fdb', 'nome' => 'LOJA 5'],
    ['path' => 'localhost:C:\\DinizSoft\\TUPIPTA\\TUPIPAULISTA.fdb', 'nome' => 'LOJA 6'],
    ['path' => 'localhost:C:\\DinizSoft\\FLORIDAPTA\\FloridaPaulista.fdb', 'nome' => 'LOJA 7'],
    ['path' => 'localhost:C:\\DinizSoft\\ADAMANTINA\\Adamantina.fdb', 'nome' => 'LOJA 8'],
    ['path' => 'localhost:C:\\DinizSoft\\BIGOCZ\\Gerencial3.fdb', 'nome' => 'LOJA 9'],
];
$username = 'SYSDBA';
$password = 'masterkey';

// Variável para definir o ambiente (0 = Teste, 1 = Produção)
$ambiente = 0;

foreach ($databases as $db) {
    try {
        // Conectar ao banco de dados usando PDO
        $pdo = new PDO("firebird:dbname=" . $db['path'], $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        //echo 'Conectado ao banco de dados: ' . $db['nome'] . '<br>';



    } catch (PDOException $e) {
        echo 'Erro ao conectar ao banco de dados: ' . $db['nome'] . ' - ' . $e->getMessage() . '<br>';
    }
}
?>
