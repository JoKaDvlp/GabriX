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
mkdir('lib');
mkdir('src');
mkdir('src/Manager');
mkdir('src/Controllers');
mkdir('src/Entity');
mkdir('src/Middlewares');
mkdir('public');
mkdir('public/js');
mkdir('public/css');
mkdir('public/images');
mkdir('storage');
mkdir('templates');
mkdir('templates/pages');
mkdir('templates/pages/app');
mkdir('templates/pages/erreur');

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
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Lancer le routeur
$router->dispatch($path);
INDEX;
file_put_contents('public/index.php', $index);

// Création de la page 404
$index404 = <<<'INDEX404'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 : page introuvable</title>
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>
    <h1>404</h1>
    <p>Page non trouvée</p>
    <p>La page que vous recherchez n'existe pas.</p>
    <a href="/" title="Retour à l'accueil">Retour à l'accueil</a>
</body>
</html>
INDEX404;
file_put_contents('templates/pages/erreur/404.html', $index404);

// Création de base.html.twig
$baseTwig = <<<'BASETWIG'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{% block title %}Bienvenue!{% endblock %}</title>
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>
    {% block body %}{% endblock %}
    <script src="/js/app.js" defer></script>
</body>
</html>
BASETWIG;
file_put_contents('templates/pages/base.html.twig', $baseTwig);

// Création de l'index HTML
$indexHtml = <<<'INDEXHTML'
{% extends 'base.html.twig' %}

{% block title %}Votre titre de page ici{% endblock %}

{% block body %}

{# Le contenu de votre page ici #}

{% endblock %}
INDEXHTML;
file_put_contents('templates/pages/app/index.html.twig', $indexHtml);


// Création du fichier css
file_put_contents('public/css/style.css', "");

// Création du fichier js
$fichierJs = <<<FICHIERJS
setTimeout(() => {
    document.querySelector(".flash").remove()
}, 2000);
FICHIERJS;
file_put_contents('public/js/app.js', "");

// Création du fichier AuthMiddleware.php
$authMiddleware = <<<'AUTHMIDDLEWARE'
<?php
namespace App\Middlewares;

use Gabrix\Session;

class AuthMiddleware {
    public function handle() {
        if (!Session::isconnected()) {
            // L'utilisateur n'est pas connecté, on renvoie un code 403
            http_response_code(403);
            include __DIR__ . '/../../templates/pages/erreur/403.html';
            return false;
        }
        return true;
    }
}
AUTHMIDDLEWARE;
file_put_contents('src/Middlewares/AuthMiddleware.php', $authMiddleware);

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
        // Suppression des slashs inutiles et ajout d'un slash au début et à la fin
        $path = trim($path, '/');
        $path = "/{$path}/";
        
        // Remplacer les paramètres dynamiques comme {etablissement}, {niveau}, {matiere}
        // On permet de capturer des nombres et des chaînes alphanumériques
        $path = preg_replace('#{([\w]+)}#', '(?P<\1>[\w-]+)', $path); // Capturer et nommer les paramètres
        
        // Remplacer les multiples slashes
        $path = preg_replace('#[/]{2,}#', '/', $path);
        
        return $path;
    }

    public function dispatch($path) {
        // Normalisation du chemin
        $path = $this->normalizePath($path);
        $method = strtoupper($_SERVER['REQUEST_METHOD']);
        
        // Vérification de l'existence d'un fichier dans le dossier public
        $filePath = __DIR__ . '/../public' . $path;
        if (file_exists($filePath) && !is_dir($filePath)) {
            return readfile($filePath);
        }
    
        foreach ($this->routes as $route) {
            if (preg_match("#^{$route['path']}$#", $path, $matches) && $route['method'] === $method) {
                // Vérification des middlewares
                if (!empty($route['middlewares'])) {
                    foreach ($route['middlewares'] as $middleware) {
                        $middlewareInstance = new $middleware();
                        if (!$middlewareInstance->handle($_SERVER['REQUEST_URI'])) {
                            return; // Middleware a bloqué la requête
                        }
                    }
                }
    
                // Extraire les paramètres nommés depuis l'URL
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
    
                // Appel du contrôleur et de la méthode correspondants
                [$class, $function] = $route['controller'];
                $controllerInstance = new $class;
                
                // Appel dynamique de la méthode du contrôleur avec les paramètres
                $content = call_user_func_array([$controllerInstance, $function], $params);
                echo $content;
                return;
            }
        }
    
        // Gérer les routes non trouvées (404)
        http_response_code(404);
        include __DIR__ . '/../templates/pages/erreur/404.html';
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
        return $this->render('app/index.php');
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
    <link rel="stylesheet" href="/css/style.css">
    <link rel="shortcut icon" href="/favicon.png" type="image/png">
</head>
<body>
    <h1>GabriX : framework PHP light</h1>
</body>
</html>
HTMLPAGE;
file_put_contents('templates/pages/app/index.html.twig', $htmlPage);

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
            throw new Exception('Namespace « ' . $namespaceParts[0] . ' » invalide. Un namespace doit commencer par : « GabriX » ou « App »', 1);
            
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

require_once "TwigConfiguration.php";

// Rendre Twig accessible globalement (optionnel)
$GLOBALS['twig'] = $twig;
INIT;
file_put_contents('lib/Init.php', $initFile);

// Création de l'abstractController
$abstractControllerClass = <<<'ABSTRACTCONTROLLER'
<?php
namespace GabriX;

// Class abstractController : class générique de gestion des controlleurs

class abstractController {

    /**
     * Ajoute un message flash dans la session
     * 
     * @param string $type Le type de message (ex: 'success', 'error', etc.)
     * @param string $message Le message à afficher
     */
    protected function addFlash($type, $message) {
        if (!isset($_SESSION)) {
            session_start(); // Démarre la session si elle n'est pas déjà démarrée
        }

        // Vérifie si un tableau de messages flash existe déjà
        if (!isset($_SESSION['flashes'])) {
            $_SESSION['flashes'] = [];
        }

        // Ajoute le message flash au tableau
        $_SESSION['flashes'][$type][] = $message;
    }

    /**
     * Méthode pour rendre une vue avec Twig
     * 
     * @param string $view Le template à utiliser 
     * @param array $parameters Les paramètres à passer à la vue
     * @return string
     */
    protected function render($view, $parameters = []) {
        if (!isset($_SESSION)) {
            session_start(); // Démarre la session si nécessaire
        }

        // Récupère les messages flash et les passe aux paramètres Twig
        $flashes = $_SESSION['flashes'] ?? [];
        unset($_SESSION['flashes']); // Efface les flashs une fois récupérés

        // Ajoute les flashs aux paramètres de rendu Twig
        $parameters['flashes'] = $flashes;

        // Utilisation de Twig pour le rendu
        return $GLOBALS['twig']->render($view, $parameters);
    }

    /**
     * Redirige vers une route donnée
     * 
     * @param string $url L'URL de la redirection
     */
    protected function redirectToRoute($url, $params = []) {
        if (!empty($params)) {
            $_SESSION['redirect_params'] = $params;
        }

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
use PDOException;
use InvalidArgumentException;

require '../config/database.php';

abstract class AbstractManager{
    private $db;

    private function connect() {
        // Rôle : Etablie une connexion à la base de données
        // paramètres : néant
        // Retour : La connexion à la BDD via l'objet PDO
        if ($this->db == null) {
            $this->db = new PDO(
                "mysql:host=" . DB_INFOS['host'] . ";port=" . DB_INFOS['port'] . ";dbname=" . DB_INFOS['dbname'],
                DB_INFOS['username'],
                DB_INFOS['password']
            );
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->exec("SET NAMES utf8");
        }
        return $this->db;
    }

    private function executeQuery($query, $params = []){
        // Rôle : exécuter les reqûetes sql
        // Paramètres :
        //      $query (string) : la requête SQL à exécuter
        //      $params (array) : un tableau de paramètre à binder si la requête contient des marqueurs ":"
        // Retour : la requête exécutée
        $stmt = $this->connect()->prepare($query);
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

    protected function readOne($class, $filters, $joins = []){
        // Rôle : Récupère une ressource de la BDD
        // Paramètres :
        //          $class (string) : le namespace d'une entité
        //          $filters (array) : un tableau de critères de filtres de la ressource
        // Retour : un objet en cas de succès, false sinon
        if (empty($filters)) {
            throw new InvalidArgumentException('Le filtre est obligatoire.');
        }
        // Initialisation de la requête de base
        $baseTable = $this->getTableName($class);
        $query = "";

        // Construction de la chaîne des jointures
        $joinClause = '';
        $selectFields = 't1.*'; // Par défaut on récupère les champs de t1
        // Dans le cas d'une requête avec jointure
        if (!empty($joins)) {
            foreach ($joins as $table => $joinData) {
                $alias = isset($joinData['alias']) ? $joinData['alias'] : $table;
                $fields = isset($joinData['fields']) ? $joinData['fields'] : '*';
                $condition = isset($joinData['condition']) ? $joinData['condition'] : '';
                $type = isset($joinData['type']) ? $joinData['type'] : 'INNER'; // Par défaut INNER JOIN

                // Ajout des champs sélectionnés
                $selectFields .= ", $fields";

                // Construction de la clause de jointure
                if (!empty($condition)) {
                    $joinClause .= " $type JOIN $table $alias ON $condition";
                }
            }
            // Ajout des champs sélectionnés à la requête
            $query = "SELECT $selectFields FROM $baseTable t1 $joinClause";
                
            // Ajout des filtres
            if (!empty($filters)) {
                $query .= ' WHERE ';
                foreach ($filters as $filter => $value) {
                    if (substr($value, 0, 1) === "%" || substr($value, -1) === "%") {
                        $query .= "t1." . $filter . " LIKE :" . $filter;
                    } else {
                        $query .= "t1." . $filter . " = :" . $filter;
                    }
                    if ($filter != array_key_last($filters)) $query .= ' AND ';
                }
            }
        } else {
            // Dans le cas d'une requête sans jointure ajout des champs sélectionnés à la requête
            $query = "SELECT $selectFields FROM $baseTable t1 $joinClause";
                
            // Ajout des filtres
            if (!empty($filters)) {
                $query .= ' WHERE ';
                foreach ($filters as $filter => $value) {
                    if (substr($value, 0, 1) === "%" || substr($value, -1) === "%") {
                        $query .= $filter . " LIKE :" . $filter;
                    } else {
                        $query .= $filter . " = :" . $filter;
                    }
                    if ($filter != array_key_last($filters)) $query .= ' AND ';
                }
            }
        }

        try {
            // Exécution de la requête
            $stmt = $this->executeQuery($query, $filters);
            $stmt->setFetchMode(PDO::FETCH_CLASS, $class);
            // Retourne l'objet ou false si non trouvé
            return $stmt->fetch();
        } catch (PDOException $e) {
            // Log l'erreur ou renvoyer false
            error_log('Database error: ' . $e->getMessage());
            return false;
        }
    }

    protected function readMany($class, $filters = [], $order = [], $limit = null, $offset = null, $joins = []) {
        // Rôle : Récupère plusieurs ressources dans la BDD avec possibilité de jointures
        // Paramètres :
        //          $class (string) : le namespace d'une entité
        //          $filters (array) : un tableau de critères de filtres des ressources
        //          $order (array) : un tableau de critères de tri des ressources. Ex : ['price' => 'ASC', 'views' => 'DESC']
        //          $limit (int) : un nombre limitant la quantité de ressources à récupérer
        //          $offset (int) : un nombre spécifiant un décalage pour la récupération de ressources ("à partir de telle ligne")
        //          $joins (array) : un tableau de jointures sous la forme ['table_name' => ['alias' => 't2', 'fields' => 't1.id, t1.fields1, t1.fields2, t2.fields', 'condition' => 't1.fields3 = t2.id', 'type' => 'LEFT']]
        // Retour : un tableau d'objets en cas de succès, false sinon

    
        // Initialisation de la requête de base
        $baseTable = $this->getTableName($class);
        $query = "";

        // Construction de la chaîne des jointures
        $joinClause = '';
        $selectFields = 't1.*'; // Par défaut on récupère les champs de t1
        if (!empty($joins)) {
            foreach ($joins as $table => $joinData) {
                $alias = isset($joinData['alias']) ? $joinData['alias'] : $table;
                $fields = isset($joinData['fields']) ? $joinData['fields'] : '*';
                $condition = isset($joinData['condition']) ? $joinData['condition'] : '';
                $type = isset($joinData['type']) ? $joinData['type'] : 'INNER'; // Par défaut INNER JOIN

                // Ajout des champs sélectionnés
                $selectFields .= ", $fields";

                // Construction de la clause de jointure
                if (!empty($condition)) {
                    $joinClause .= " $type JOIN $table $alias ON $condition";
                }
            }
        }

        // Ajout des champs sélectionnés à la requête
        $query = "SELECT $selectFields FROM $baseTable t1 $joinClause";
        
        // Ajout des filtres
        if (!empty($filters)) {
            $query .= ' WHERE ';
            foreach ($filters as $filter => $value) {
                if (substr($value, 0, 1) === "%" || substr($value, -1) === "%") {
                    $query .= $filter . " LIKE :" . $filter;
                } else {
                    $query .= $filter . " = :" . $filter;
                }
                if ($filter != array_key_last($filters)) $query .= ' AND ';
            }
        }
    
        // Ajout des critères de tri
        if (!empty($order)) {
            $query .= ' ORDER BY ';
            foreach ($order as $key => $val) {
                $query .= $key . ' ' . $val;
                if ($key != array_key_last($order)) $query .= ', ';
            }
        }
    
        // Ajout des limites et offset
        if (isset($limit)) {
            $query .= ' LIMIT ' . $limit;
            if (isset($offset)) {
                $query .= ' OFFSET ' . $offset;
            }
        }

        // Exécution de la requête
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
        $stmt = $this->executeQuery($query, $fields);

        if ($stmt) {
            return $this->db->lastInsertId();
        } else {
            return false;
        }
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

// Création de TwigConfiguration.php
$twigConfiguration = <<<'TWIGCONFIGURATION'
<?php

use Gabrix\Session;

require_once __DIR__ . '/../vendor/autoload.php';

// Configuration de Twig
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates/pages');
$twig = new \Twig\Environment($loader, [
    'cache' => __DIR__ . '/../cache',  // Mise en cache des pages twig déjà compilées
    'debug' => true, // Activer le mode debug en développement
]);

// Activer le débogage de Twig
$twig->addExtension(new \Twig\Extension\DebugExtension());

// Fonction asset pour générer les URLs des ressources statiques
function asset($path) {
    return "/" . ltrim($path, '/');
}
// Ajouter la fonction asset à Twig
$twig->addFunction(new \Twig\TwigFunction('asset', function ($path) {
    return asset($path);
}));

// Fonction pour obtenir l'utilisateur connecté
function utilisateurConnecte(){
    return Session::userconnected() ?? null;
}
// Ajouter la fonction utilisateurConnecte à Twig
$twig->addFunction(new \Twig\TwigFunction('utilisateurConnecte', function () {
    return utilisateurConnecte();
}));

// Rendre Twig accessible globalement
$GLOBALS['twig'] = $twig;
TWIGCONFIGURATION;
file_put_contents('lib/TwigConfiguration.php', $twigConfiguration);

// Création du .htaccess
$htaccess = <<<HTACCESS
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
HTACCESS;
file_put_contents('public/.htaccess', $htaccess);

// Installer des dépendances via Composer
echo "Installation de Twig\n";
exec('composer require twig/twig:"^3.0"');

// Installation de npm et installation de Sass via npm
echo "Voulez-vous installer Sass ? (y/".text("n", "yellow").") : ";
if (trim(fgets(STDIN)) === "y") {
    echo "Initialisation de Sass... \n";
    exec('npm init -y');
    
    echo "Installation de Sass...\n";
    exec("npm install sass --save-dev");

    // Créer le dossier assets contenant le fichier main.scss et le dossier components
    mkdir('assets', 0777, true);
    mkdir('assets/components', 0777, true);

    // Création du fichier main.scss
    $mainScss = <<<MAINSCSS
    @import "./_variables.scss";
    MAINSCSS;
    file_put_contents('assets/main.scss', $mainScss);

    // Création du fichier de variables scss avec la fonction de gestion des largueur de colonnes dans un système flexbox
    $variablesScss = <<<'VARIABLESSCSS'
    /* Merci Nico pour la fonction ;) */
    
    $gutter: 16px;

    @function large($i){
        @return calc((100% / (12 / $i)) - (((12 / $i) - 1) * $gutter) / (12 / $i)); 
    }

    @mixin largeur-modifier {
        @for $i from 1 to 13{
            &-#{$i}{
            width : large($i);
            }
        }
    }

    /*Generation des classes pour la largeur des colonnes selon 
    les resolutions d'ecrans*/

    /*Desktop*/
    .large{
        @include largeur-modifier;
    }

    /*Tablets*/
    @media all and (max-width : 700px){
        .medium{
        @include largeur-modifier;
        }
    }

    /*Smartphones*/
    @media all and (max-width : 400px){
        .small{
            @include largeur-modifier;
        }
    }
    VARIABLESSCSS;
    file_put_contents('assets/_variables.scss', $variablesScss);

    // Ajout du fichier _flash.scss
    $flashScss = <<<'FLASHSCSS'
    .flash{
        position: fixed;
        width: 100vw;
        padding: 50px;
    }

    .flash-success{
        color: white;
        background-color: rgb(0, 156, 0);
    }
    .flash-error{
        color: white;
        background-color: rgb(201, 0, 0);
    }
    .flash-warning{
        color: black;
        background-color: orange;
    }
    FLASHSCSS;
    file_put_contents('assets/components/_flash.scss', $flashScss);
    
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
} else {
    exit;
}
?>