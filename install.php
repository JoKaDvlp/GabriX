<?php

include "colors.php";

// Affiche un message d'accueil
$accueil = "--- Bienvenue dans l'installateur du framework GabriX. ---";
$nbCaracteres = strlen($accueil);
echo text(str_repeat("-", $nbCaracteres)."\n", "red", "white");
echo text("$accueil\n", "red", "white");
echo text(str_repeat("-", $nbCaracteres)."\n", "red", "white");

// Demander le nom du projet
echo text("\nEntrez le nom de votre projet : ", "blue");
$nomProjet = trim(fgets(STDIN));

// Créer un dossier pour le projet
if (!mkdir($nomProjet, 0777, true)) {
    die(text("Erreur lors de la création du dossier du projet.\n", "red"));
}
echo text("\nDossier '$nomProjet' créé avec succès.\n\n", "green");

// Initialiser le projet avec Composer
chdir($nomProjet); // Se déplacer dans le dossier du projet
echo "Initialisation de Composer...\n";
exec("composer init --no-interaction --name=\"$nomProjet/$nomProjet\" --description=\"Un projet PHP\" --require=\"php:>=8.0\"");

// Créer la structure de base du projet
echo "Création de la structure de fichiers...\n";
mkdir('config');
mkdir('config/Middlewares');
mkdir('lib');
mkdir('lib/Controller');
mkdir('src');
mkdir('src/Manager');
mkdir('src/Controllers');
mkdir('src/Entity');
mkdir('public');
mkdir('public/js');
mkdir('public/css');
mkdir('public/images');
mkdir('storage');
mkdir('templates');
mkdir('templates/pages');

// Création de la variable database
$databaseInfos = <<<'DBINFOS'
<?php
const DB_INFOS = [
	'host'     => '127.0.0.1',
	'port'     => '3306',
	'dbname'   => 'nom_bdd',
	'username' => 'root',
	'password' => ''
];
DBINFOS;
file_put_contents('config/database.php', $databaseInfos);

// Création de l'index
file_put_contents('public/index.php', "<?php\n\n// Point d'entrée de l'application\n");

// Création du fichier css
file_put_contents('public/css/style.css', "");

// Création du fichier js
file_put_contents('public/js/app.js', "");

// Création de la classe router
$routerClass = <<<'ROUTER'
<?php

// Class router: classe de gestion des routes du projet

namespace App\Utils;

class router {
    private $routes = []; // Tableau des routes du projet de la forme ['/chemin/absolu', 'method', [class, 'controller']]

    public function add($methods, $path, $controller, $middlewares = []) {
        // Rôle : Ajouter une route au tableau $routes
        // Paramètres : 
        //          $methods : tableau de requête pour accéder à la page
        //          $path : chaine de caractères décrivant le chemin absolu de la page
        //          $controller : tableau de la forme [$classe, $methode]
        if (empty($methods)) {
            $methods = ['GET'];
        }
        $path = $this->normalizePath($path);
        foreach ($methods as $method) {
            $this->routes[] = ['path' => $path, 'method' => strtoupper($method), 'controller' => $controller, 'middlewares' => $middlewares];
        }
    }

    private function normalizePath($path) {
        // Rôle : Normaliser le chemin pour éviter les erreurs
        // Paramètres :
        //          $path : chaine de caractère du chemin à traiter
        // Retour : La chaine de caractères du chemin normalisé
        $path = trim($path, '/');
        $path = "/{$path}/";
        $path = preg_replace('#{[\w]+}#', '([\w]+)', $path); // Permet de gérer les paramètres dynamiques
        $path = preg_replace('#[/]{2,}#', '/', $path);
        return $path;
    }

    public function dispatch($path) {
        $path = $this->normalizePath($path);
        $method = strtoupper($_SERVER['REQUEST_METHOD']);
        foreach ($this->routes as $route) {
            if (preg_match("#^{$route['path']}$#", $path, $matches) && $route['method'] === $method) {
                if (!empty($route['middlewares'])) {
                    foreach ($route['middlewares'] as $middleware) {
                        $middlewareInstance = new $middleware();
                        if (!$middlewareInstance->handle()) {
                            return;
                        }
                    }
                }
                array_shift($matches); // Supprime la correspondance complète de $matches
                [$class, $function] = $route['controller'];
                $controllerInstance = new $class;
                $content = call_user_func_array([$controllerInstance, $function], $matches);
                echo $content;
                return;
            }
        }
        // Gérer la route non trouvée
        http_response_code(404);
        include "templates/pages/404.php";
    }
}
ROUTER;
file_put_contents('lib/Router.php', $routerClass);

// Création de la classe Session
$sessionClass = <<<'SESSION'
<?php
namespace App\Utils;

// Classe session : classe de gestion de la session

use App\Entity\User;

class Session {

    static function activation() {
        // Rôle : Démarrer la session
        // Paramètres : néant
        // Retour : True si la session est connecté, false sinon

        // Démarrer le mécanisme
        session_start();

        // Si un utilisateur est connecté
        if (self::isconnected()){
            global $utilisateurConnecte;
            $utilisateurConnecte = new User(self::idconnected());
        }

        // Retourner si on est connecté ou pas
        return self::isconnected();
    }

    static function isconnected() {
        // Rôle : Dire si il y a une connexion active
        // Paramètres : néant
        // Retour : true si un utilisateur est connecté, false sinon

        return ! empty($_SESSION["id"]);
    }

    static function idconnected() {
        // Rôle : Renvoyer l'id de l'utilisateur connecté
        // Paramètres : néant
        // Retour : L'id de l'utilisateur connecté ou 0

        if (self::isconnected()) {
            return $_SESSION["id"];
        } else {
            return 0;
        }
    }

    static function userconnected() {
        // Rôle : retourner un objet utilisateur chargé à parti de l'idconnected
        // Paramètres : néant
        // Retour : Un objet utilisateur chargé

        if(self::isconnected()) {
            return new User(self::idconnected());
        } else {
            return new User();
        }
    }

    static function deconnect() {
        // Rôle : déconnecter la session courante
        // Paramètres : néant
        // Retour : true

        $_SESSION["id"] = 0;
    }

    static function connect($id) {
        // Rôle : connecter l'utilisateur
        // Paramètres :
        //      $id : id de l'utilisateur à connecter
        // Retour : true

        $_SESSION["id"] = $id;
    }
}
SESSION;
file_put_contents('lib/Session.php', $sessionClass);

// Création de la class Autoloader
$autoloaderClass = <<<'AUTOLOADER'
<?php
namespace App\Utils;

class Autoloader{
    /**
     * 
     */
    static function register(){
        spl_autoload_register([__CLASS__, "autoloadClasses"]);
    }

    /**
     * 
     */
    static function autoloadClasses($class){
        $classPath = str_replace("App"."\\","",$class);
        $classPath = str_replace("\\","/",$classPath). ".php";
        // echo $classPath." trouvée";
        // echo "<br>";
        if (file_exists(__DIR__."/../".$classPath)) {
            require __DIR__."/../".$classPath;
        } else {
            echo "Classe " . $classPath . " non trouvée";
        }
    }
}
AUTOLOADER;
file_put_contents('lib/Autoloader.php', $autoloaderClass);

// Création du fichier d'init
$initFile = <<<'INIT'
<?php

// Code d'initialisation à inclure en début de contrôleur

// Paramétrer l'affichage des erreurs

use App\Utils\Autoloader;
use App\Utils\Session;

ini_set("display_errors", 1);   // Afficher les erreurs
error_reporting(E_ALL);         // Toutes les erreurs

// Ouverture de la BDD dans une variable globale $bdd
global $bdd;
$bdd = new PDO("mysql:host=localhost;dbname=projets_exam-back_jkarmann;charset=UTF8", "jkarmann", "KU=WFl4d?G");
$bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);

// Autochargement des classes
require "autoloader.php";
if (class_exists("App\Utils\Autoloader")) {
    Autoloader::register();
} else {
    echo "Erreur, autoloader introuvable...";
}

// Activation de la session
session::activation();
INIT;
file_put_contents('lib/Init.php', $initFile);

// Création de l'abstractController
$abstractControllerClass = <<<'ABSTRACTCONTROLLER'
<?php
namespace App\Utils;

// Class abstractController : class générique de gestion des controlleurs

class abstractController {

    protected function render($view, $parameter = []){
        // Rôle : Extrait les données nécessaire à la construction du template et inclut le template
        // Paramètres :
        //          $view : chemin du template à afficher
        //          $parameter : tableau des données nécessaires à la construction du template
        // Retour : le template rendu
        ob_start();
        extract($parameter);
        require "/templates/pages/" . $view . ".html.twig";            
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    protected function redirectToRoute($url){
        // Rôle : Redirige vers l'url ou la route spécifiée
        // Paramètres :
        //          $url : l'URL ou la route vers laquelle rediriger
        // Retour : néant
        header("Location: {$url}");
        exit;
    }

}
ABSTRACTCONTROLLER;
file_put_contents('lib/AbstractController.php', $abstractControllerClass);

// Création de l'abstractManager
$abstractManagerClass = <<<'ABSTRACTMANAGER'
<?php

namespace GabriX\Manager;

use PDO;

require dirname(__DIR__,2) . '/config/database.php';

abstract class AbstractManager{

    private function connect() {
        // Rôle : Etablie une connexion à la base de données
        // paramètres : néant
        // Retour : La connexion à la BDD via l'objet PDO
        $db = new PDO(
            "mysql:host=" . DB_INFOS['host'] . ";port=" . DB_INFOS['port'] . ";dbname=" . DB_INFOS['dbname'],
            DB_INFOS['username'],
            DB_INFOS['password']
        );
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec("SET NAMES utf8");
        return $db;
    }

    private function executeQuery($query, $params = []){
        // Rôle : exécuter les reqûetes sql
        // Paramètres :
        //      $query (string) : la requête SQL à exécuter
        //      $params (array) : un tableau de paramètre à binder si la requête contient des marqueurs ":"
        // Retour : la requête exécutée
        $db = $this->connect();
        $stmt = $db->prepare($query);
        foreach ($params as $key => $param) $stmt->bindValue($key, $param);
        $stmt->execute();
        return $stmt;
    }

    private function getTableName($class){
        // Rôle : Récupère le nom de la table associé à l'entité
        // Paramètres :
        //          $class (string) : le namespace d'une entité
        // Retour : le nom de la table
        if (defined($class . '::TABLE_NAME')) {
            $table = $class::TABLE_NAME;
        } else {
            $tmp = explode('\\', $class);
            $table = strtolower((end($tmp)));
        }
        return $table;
    }

    protected function readOne($class, $filters){
        // Rôle : Récupère une ressource de la BDD
        // Paramètres :
        //          $class (string) : le namespace d'une entité
        //          $filters (array) : un tableau de critères de filtres de la ressource
        // Retour : un objet en cas de succès, false sinon
        $query = 'SELECT * FROM' . $this->getTableName($class) . ' WHERE ';
        foreach (array_keys($filters) as $filter) {
            $query .= $filter . " = :" . $filter;
            if ($filter != array_key_last($filters)) $query .= ' AND ';
        }
        $stmt = $this->executeQuery($query, $filters);
        $stmt->setFetchMode(PDO::FETCH_CLASS, $class);
        return $stmt->fetch();
    }

    protected function readMany($class, $filters = [], $order = [], $limit = null, $offset = null){
        // Rôle : Récupère plusieurs ressources dans la BDD
        // Paramètres :
        //          $class (string) : le namespace d'une entité
        //          $filters (array) : un tableau de critères de filtres des ressources
        //          $order (array) : un tableau de critères de tri des ressources. exemples : ['price' => 'ASC', 'views' => 'DESC']
        //          $limit (int) : un nombre limitant la quantité de ressources à récupérer
        //          $offset (int) : un nombre spécifiant un décalage pour la récupération de ressources ("à partir de telle ligne")
        // Retour : un tableau d'objets en cas de succès, false sinon
		$query = 'SELECT * FROM ' . $this->getTableName($class);
		if (!empty($filters)) {
			$query .= ' WHERE ';
			foreach (array_keys($filters) as $filter) {
				$query .= $filter . " = :" . $filter;
				if ($filter != array_key_last($filters)) $query .= ' AND ';
			}
		}
		if (!empty($order)) {
			$query .= ' ORDER BY ';
			foreach ($order as $key => $val) {
				$query .= $key . ' ' . $val;
				if ($key != array_key_last($order)) $query .= ', ';
			}
		}
		if (isset($limit)) {
			$query .= ' LIMIT ' . $limit;
			if (isset($offset)) {
				$query .= ' OFFSET ' . $offset;
			}
		}
		$stmt = $this->executeQuery($query, $filters);
		$stmt->setFetchMode(PDO::FETCH_CLASS, $class);
		return $stmt->fetchAll();
	}

    protected function create($class, $fields){
        // Rôle : enregistre une ressource au sein d'une table
        // Paramètres :
        //          $class (string) : le namesace d'une entité
        //          $fields (array) : les champs à enregistrer en BDD
        // Retour : Une instance de PDOStatement en cas de succès, false sinon
		$query = "INSERT INTO " . $this->getTableName($class) . " (";
		foreach (array_keys($fields) as $field) {
			$query .= $field;
			if ($field != array_key_last($fields)) $query .= ', ';
		}
		$query .= ') VALUES (';
		foreach (array_keys($fields) as $field) {
			$query .= ':' . $field;
			if ($field != array_key_last($fields)) $query .= ', ';
		}
		$query .= ')';
		return $this->executeQuery($query, $fields);
	}

    protected function update($class, $fields, $id){
        // Rôle : Modifie une ressource au sein d'une table
        // Paramètres :
        //          $class (string) : le amespace d'une entité
        //          $fields (array) : les champs à modifier en BDD
        //          $id (string) : l'identifiant de la ressource à éditer
        // Retour : une instance de PDOStatement en cas de succès, false sinon
		$query = "UPDATE " . $this->getTableName($class) . " SET ";
		foreach (array_keys($fields) as $field) {
			$query .= $field . " = :" . $field;
			if ($field != array_key_last($fields)) $query .= ', ';
		}
		$query .= ' WHERE id = :id';
		$fields['id'] = $id;
		return $this->executeQuery($query, $fields);
	}

    protected function remove($class, $id){
        // Rôle : Supprime une ressource au sein d'une table
        // Paramètres :
        //          $class (string) : le namespace d'une entité
        //          $$id (int) : l'identifiant de la ressource à supprimer
        // Retour : une instance de PDOStatement en cas de succès, false sinon
		$query = "DELETE FROM " . $this->getTableName($class) . " WHERE id = :id";
		return $this->executeQuery($query, [ 'id' => $id ]);
	}

}
ABSTRACTMANAGER;
file_put_contents('src/Manager/AbstractManager.php', $abstractManagerClass);

// Création du .htaccess
$htaccess = <<<HTACCESS
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
HTACCESS;
file_put_contents('.htaccess', $htaccess);

// Installer des dépendances via Composer
echo "Installation de certaines dépendances...\n";
exec('composer require twig/twig:"^3.0"');

// Installation de npm et installation de Sass via npm
echo "Initialisation de Sass... \n";
exec('npm init -y');

echo "Installation de Sass...\n";
exec("npm install sass --save-dev");

// Modifier le fichier package.json pour ajouter des scripts
$packageJsonPath = 'package.json';

// Vérifier que le fichier package.json existe
if (file_exists($packageJsonPath)) {
    // Lire le contenu du fichier package.json
    $packageJson = file_get_contents($packageJsonPath);

    // Convertir le contenu JSON en tableau associatif PHP
    $packageData = json_decode($packageJson, true);

    // Ajouter ou modifier la section "scripts"
    $packageData['scripts'] = [
        "sass-dev" => "sass --watch assets/main.scss public/css/style.css",
        "sass-prod" => "sass --style=compressed assets/main.scss public/css/style.min.css"
    ];

    // Convertir le tableau PHP en JSON avec une belle mise en forme
    $newPackageJson = json_encode($packageData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    // Écrire les modifications dans le fichier package.json
    file_put_contents($packageJsonPath, $newPackageJson);

    echo "Scripts ajoutés avec succès dans package.json.\n";
} else {
    echo "Erreur : le fichier package.json n'a pas été trouvé.\n";
}

echo text("\nInstallation terminée avec succès. Vous pouvez commencer à développer votre projet dans le dossier '$nomProjet'.\n", "green");

?>