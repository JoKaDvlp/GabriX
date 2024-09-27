<?php

require "colors.php";

// start-server.php

// Récupérer le chemin de base du projet (en supposant que ce fichier est à la racine de votre projet)
$defaultAppPath = getcwd() . DIRECTORY_SEPARATOR . 'public'; // Utilisez un chemin relatif
$entryPoint = $defaultAppPath . DIRECTORY_SEPARATOR . 'index.php'; // Point d'entrée du serveur

// Vérifiez si un chemin personnalisé a été fourni en argument
if (isset($argv[1])) {
    $customAppPath = realpath($argv[1]);
    if ($customAppPath !== false && is_dir($customAppPath)) {
        $appPath = $customAppPath;
        $entryPoint = $appPath . DIRECTORY_SEPARATOR . 'index.php';
    } else {
        die("Le chemin fourni n'est pas valide.\n");
    }
} else {
    $appPath = $defaultAppPath;
}

// Vérifiez si le fichier d'entrée (index.php) existe
if (!file_exists($entryPoint)) {
    die("Le fichier d'entrée '$entryPoint' n'existe pas. Veuillez vérifier votre structure de répertoires.\n");
}

// Permettre à l'utilisateur de définir le port en argument (par défaut: 8000)
$host = '127.0.0.1';
$port = isset($argv[2]) ? $argv[2] : 8000;

// Construire la commande pour démarrer le serveur PHP intégré
$command = sprintf('php -S %s:%d -t "%s" "%s"', $host, $port, $appPath, $entryPoint);

// Afficher des informations à l'utilisateur
$messageServer = "--- Démarrage du serveur sur http://$host:$port ---";
echo text(str_repeat("-",strlen($messageServer))."\n", "black", "green");
echo text($messageServer."\n", "black", "green");
echo text(str_repeat("-",strlen($messageServer))."\n", "black", "green");
echo "Document root: $appPath\n";
echo "Fichier d'entrée: $entryPoint\n";

// Exécuter la commande
passthru($command);