<?php

include "colors.php";

// Affiche un message d'accueil
$accueil = "--- Bienvenue dans l'installateur du framework GabriX. ---";
$nbCaracteres = strlen($accueil);
echo text("\n".str_repeat("-", $nbCaracteres)."\n", "red");
echo text("$accueil\n", "red");
echo text(str_repeat("-", $nbCaracteres)."\n", "red");

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
mkdir('templates/pages/app');

// Création de l'index
$index = <<<'INDEX'
<?php

use App\Controllers\AppController;
use GabriX\Router;

require __DIR__ . '/../lib/Init.php';

// Point d'entrée de l'application

$router = new Router();
$router->registerRoutesFromController(AppController::class);
// Routes créées

// Récupérer l'URL actuelle
$path = $_SERVER['REQUEST_URI'];

// Lancer le routeur
$router->dispatch($path);
INDEX;
file_put_contents('public/index.php', $index);+

// Création de la page 404
$index404 = <<<'INDEX404'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 : page introuvable</title>
    <link rel="stylesheet" href="/public/css/style.css">
    <link rel="shortcut icon" href="/storage/gx_icon.png" type="/image/x-icon">
</head>
<body>
    <h1>404</h1>
    <p>Page non trouvée</p>
    <p>La page que vous recherchez n'existe pas.</p>
    <a href="/" title="Retour à l'accueil">Retour à l'accueil</a>
</body>
</html>
INDEX404;
file_put_contents('/templates/pages/erreur/404.html');

// Création de l'index HTML
$indexHtml = <<<'INDEXHTML'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>
    <h1>Titre de notre test</h1>
</body>
</html>
INDEXHTML;


// Création du fichier css
file_put_contents('public/css/style.css', "");

// Création du fichier js
file_put_contents('public/js/app.js', "");

// Création de la classe router
$routerClass = <<<'ROUTER'
<?php

// Class router: classe de gestion des routes du projet

namespace GabriX;

class Router {
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

    // Scanner une classe pour les attributs de route
    public function registerRoutesFromController(string $controllerClass): void {
        $reflectionClass = new \ReflectionClass($controllerClass);
        
        // Gestion des attributs au niveau de la classe (préfixes de routes)
        $classAttributes = $reflectionClass->getAttributes(Route::class);
        $classPath = '';
        
        if (!empty($classAttributes)) {
            $classRoute = $classAttributes[0]->newInstance();
            $classPath = $classRoute->path; // Préfixe de route depuis la classe
        }

        // Parcourir les méthodes du contrôleur pour chercher les routes
        foreach ($reflectionClass->getMethods() as $method) {
            $attributes = $method->getAttributes(Route::class);
            foreach ($attributes as $attribute) {
                $route = $attribute->newInstance(); // Instancier l'attribut Route
                $fullPath = $this->normalizePath($classPath . $route->path); // Combiner les chemins de classe et méthode
                $this->add($route->methods, $fullPath, [$controllerClass, $method->getName()], $route->middlewares);
            }
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
        header("/templates/pages/erreur/404.php");
    }
}
ROUTER;
file_put_contents('lib/Router.php', $routerClass);

// Création de la classe Route
$routeClass = <<<'ROUTE'
<?php

namespace GabriX;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_CLASS)]
class Route {
    public function __construct(
        public string $path,
        public string $name = '',
        public array $methods = ['GET'],
        public array $middlewares = []
    ) {}
}
ROUTE;
file_put_contents('lib/Route.php', $routeClass);

// Création de la classe AppController
$appControllerClass = <<<'APPCONTROLLER'
<?php

namespace App\Controllers;

use GabriX\abstractController;
use GabriX\Route;

#[Route('/')]
class AppController extends abstractController{
    #[Route('', name: 'app_index')]
    public function index(){
        return $this->render('app/index.html');
    }
}
APPCONTROLLER;
file_put_contents('src/Controllers/AppController.php', $appControllerClass);

// Création de la page HTML de démo
$htmlPage = <<<'HTMLPAGE'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GabriX</title>
</head>
<body>
    <h1>GabriX : framework PHP light</h1>
</body>
</html>
HTMLPAGE;
file_put_contents('templates/pages/app/index.html', $htmlPage);

// Création de la class Autoloader
$autoloaderClass = <<<'AUTOLOADER'
<?php
namespace GabriX;

use Exception;

class Autoloader{
    static private $aliases = [
        'GabriX' => 'lib',
        'App' => 'src'
    ];
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
        
        $namespaceParts = explode("\\", $class);

        if (in_array($namespaceParts[0],array_keys(self::$aliases))) {
            $namespaceParts[0] = self::$aliases[$namespaceParts[0]];
        } else {
            throw new Exception('Namespace « ' . $namespaceParts[0] . ' » invalide. Un namespace doit commencer par : « Plugo » ou « App »', 1);
            
        }

        $filepath = __DIR__ . '/../'. implode('/', $namespaceParts) . '.php';
        if (!file_exists($filepath)) {
            throw new Exception("Fichier « " . $filepath . " » introuvable pour la classe « " . $class . " ». Vérifier le chemin, le nom de la classe ou le namespace");
        }
        require $filepath;
    }
}
AUTOLOADER;
file_put_contents('lib/Autoloader.php', $autoloaderClass);

// Création du fichier d'init
$initFile = <<<'INIT'
<?php

// Code d'initialisation à inclure en début de contrôleur

use GabriX\Autoloader;

// Paramétrer l'affichage des erreurs
ini_set("display_errors", 1);   // Afficher les erreurs
error_reporting(E_ALL);         // Toutes les erreurs

// Autochargement des classes
require "Autoloader.php";
if (class_exists("GabriX\Autoloader")) {
    Autoloader::register();
} else {
    echo "Erreur, autoloader introuvable...";
}
INIT;
file_put_contents('lib/Init.php', $initFile);

// Création de l'abstractController
$abstractControllerClass = <<<'ABSTRACTCONTROLLER'
<?php
namespace GabriX;

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
        require __DIR__ . "/../templates/pages/" . $view;            
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

namespace GabriX;

use PDO;

require '../config/database.php';

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
file_put_contents('lib/AbstractManager.php', $abstractManagerClass);

// Création du .htaccess
$htaccess = <<<HTACCESS
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
HTACCESS;
file_put_contents('.htaccess', $htaccess);

// Installer des dépendances via Composer
echo "Voulez-vous installer Twig ? (y/".text("n", "yellow").") : ";
if (trim(fgets(STDIN)) === "y") {
    echo "Installation de certaines dépendances...\n";
    exec('composer require twig/twig:"^3.0"');
}

// Installation de npm et installation de Sass via npm
echo "Voulez-vous installer Sass ? (y/".text("n", "yellow").") : ";
if (trim(fgets(STDIN)) === "y") {
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
}

echo text("\nInstallation terminée avec succès. Vous pouvez commencer à développer votre projet dans le dossier '$nomProjet'.\n\n", "green");

echo text("Voulez-vous ouvrir votre projet créé avec VSCode ? (oui par défaut) : ", "blue");
if (trim(fgets(STDIN)) === "" || trim(fgets(STDIN)) === "y") {
    exec("code .");
}
?>