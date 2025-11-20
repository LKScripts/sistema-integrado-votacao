<?php
// Conexão com o banco de dados XAMPP (MariaDB 10.4+)
$host = "localhost";
$usuario = "root";
$senha = ""; // geralmente vazio no XAMPP
$banco = "siv_db"; // Nome correto do banco conforme schema
$porta = 3306; // porta padrão do MySQL/MariaDB

// Criar conexão PDO
try {
    $dsn = "mysql:host=$host;dbname=$banco;port=$porta;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $conn = new PDO($dsn, $usuario, $senha, $options);
} catch (PDOException $e) {
    error_log($e->getMessage());
    die("Falha na conexão com o banco de dados. Entre em contato com o suporte.");
}
?>
