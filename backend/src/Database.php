<?php

// Criação da classe "Database": fornece uma conexão PDO com o banco de dados
class Database
{
    public static function getConnection(): PDO
    {
        $dbPath = __DIR__ . '/../database/database.sqlite';

        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    }
}