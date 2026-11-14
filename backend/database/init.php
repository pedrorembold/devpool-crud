<?php

//Detalha onde o arquivo do banco de dados e o schema da db estão localizados
$dbPath = __DIR__ ."/database.sqlite";
$schemaPath = __DIR__ ."/schema.sql";

//Conexão ao SQLite
$pdo = new PDO('sqlite:' . $dbPath);

//Configura o PDO para lançar exceções em caso de erro. Evita falhar em silêncio.
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//Lê o conteúdo de schema.sql e executa as instruções SQL spbre criação de tabelas
$SQL = file_get_contents($schemaPath);
$pdo->exec($SQL);

echo "Banco pronto em $dbPath" . PHP_EOL;