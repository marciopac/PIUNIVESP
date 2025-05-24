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
    ['path' => '26.114.245.178:C:\\DinizSoft\\Gerencial3\\Gerencial3.fdb', 'nome' => 'BIG-OCZ'],    
       
];
$username = 'SYSDBA';
$password = 'masterkey';

// Variável para definir o ambiente (0 = Teste, 1 = Produção)
$ambiente = 1;

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
