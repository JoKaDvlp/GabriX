<?php

include "colors.php";

// Affiche un message d'accueil
$accueil = "--- Création d'une base de données ---";
$nbCaracteres = strlen($accueil);
echo text("\n".str_repeat("-", $nbCaracteres)."\n", "red");
echo text("$accueil\n", "red");
echo text(str_repeat("-", $nbCaracteres)."\n", "red");

// Demander le nom de la base de données
echo "\nEntrez le nom de votre base de données : (par défaut ". text(basename(getcwd()), "yellow").") ";
$dbName = trim(fgets(STDIN));

if (empty($dbName)) {
    $dbName = basename(getcwd());
}

// Choix entre bdd locale avec Laragon ou bdd perso
echo "\nSouhaitez-vous utiliser une base de donnée locale avec LARAGON ? (". text("y", "yellow")."/n) : ";
$choix = trim(fgets(STDIN));

if ($choix === "" || $choix ==="y") {
    // Création du fichier database Laragon
    $host = "127.0.0.1";
    $port = "3306";
    $username = "root";
    $password = "";
} else if ($choix === "n") {
    // Demander l'hote
    echo "Entrez l'hote de votre base de données : ";
    $host = trim(fgets(STDIN));
    // Demander le port
    echo "Entrez le port de votre base de données : ";
    $port = trim(fgets(STDIN));
    // Demander le nom de la base de données
    echo "Entrez le nom de votre base de données : ";
    $dbName = trim(fgets(STDIN));
    // Demander le username
    echo "Entrez votre username : ";
    $username = trim(fgets(STDIN));
    // Demander le password
    $password = trim(fgets(STDIN));
}

// Création du fichier database
$databaseInfos = <<<DBINFOS
<?php
const DB_INFOS = [
    'host'     => '{$host}',
    'port'     => '{$port}',
    'dbname'   => '{$dbName}',
    'username' => '{$username}',
    'password' => '{$password}'
];
DBINFOS;
file_put_contents('config/database.php', $databaseInfos);

try {
    $bdd = new PDO('mysql:host='.$host.';port='.$port, $username, $password);
    
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = "CREATE DATABASE IF NOT EXISTS `$dbName`";
    $bdd->exec($sql);
    echo text("\nBase de données créée avec succès.\n", "green");
} catch (PDOException $e) {
    echo text("\nErreur : ".$e->getMessage()."\n\n", "red");
}

// Fermer la connexion
$bdd = null;
?>
