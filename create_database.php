<?php

include "colors.php";

// Demander le nom de la base de données
echo "Entrez le nom de votre base de données : (par défaut ". text(basename(getcwd()), "yellow").") ";
$dbName = trim(fgets(STDIN));

if (empty($dbName)) {
    $dbName = basename(getcwd());
}

// Création du fichier database
$databaseInfos = <<<DBINFOS
<?php
const DB_INFOS = [
	'host'     => '127.0.0.1',
	'port'     => '3306',
	'dbname'   => '{$dbName}',
	'username' => 'root',
	'password' => ''
];
DBINFOS;
file_put_contents('config/database.php', $databaseInfos);

try {
    $bdd = new PDO('mysql:host=127.0.0.1', 'root', '');
    
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = "CREATE DATABASE IF NOT EXISTS `$dbName`";
    $bdd->exec($sql);
    echo text("Base de données créée avec succès.", "green");
} catch (PDOException $e) {
    echo text("Erreur : ".$e->getMessage(), "red");
}

// Fermer la connexion
$pdo = null;
?>
