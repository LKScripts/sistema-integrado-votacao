<?php
// Conexão com o banco de dados XAMPP (porta 3307)
$host = "localhost";
$usuario = "root";
$senha = ""; // geralmente vazio no XAMPP
$banco = "siv";
$porta = 3307; // porta personalizada

// Criar conexão
$conn = new mysqli($host, $usuario, $senha, $banco, $porta);

// Checar conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
?>
