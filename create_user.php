<?php

include "colors.php";

// Vérifier si une base de données a été créé ou qu'une entité User a déjà été créé
if (!file_exists("config/database.php")) {
    echo text("\n!!! Veuillez d'abord créer une base de données afin d'ajouter les entités (GabriX create:database) !!!\n", "red");
    exit;
} else if (file_exists("src/Entity/User.php")) {
    echo text("\n!!! Vous avez déjà créé une entité User. !!!\n", "red");
    exit;
}

// Affiche un message d'accueil
$accueil = "--- Création d'un mécanisme de session avec l'entité User ---";
$nbCaracteres = strlen($accueil);
echo text("\n".str_repeat("-", $nbCaracteres)."\n", "red");
echo text("$accueil\n", "red");
echo text(str_repeat("-", $nbCaracteres)."\n", "red");

// Nom de l'entité
$nomEntite = "User";

// Création des champs de l'entité
// Tant que $nomChamp est différent de 0, je demande à ajouter un nouveau champ
$types = [
    "TINYINT" => "255",
    "SMALLINT" => "65535",
    "MEDIUMINT" => "16777215",
    "INT" => "4294967295",
    "BIGINT" => "18446744073709551615",
    "DECIMAL" => "Dépend de la précision. De la forme 5,2 pour un nombre décimal 999,99",
    "NUMERIC" => "Dépend de la précision",
    "FLOAT" => "3.402823466E+38",
    "DOUBLE" => "1.7976931348623157E+308",
    "BIT" => "64 bits",
    "BOOLEAN" => "1 ou 0",
    "CHAR" => "255 caractères",
    "VARCHAR" => "65535 caractères",
    "TEXT" => "65535 caractères",
    "TINYTEXT" => "255 caractères",
    "MEDIUMTEXT" => "16777215 caractères",
    "LONGTEXT" => "4 Go",
    "BINARY" => "255 octets",
    "VARBINARY" => "65535 octets",
    "BLOB" => "65535 octets",
    "TINYBLOB" => "255 octets",
    "MEDIUMBLOB" => "16777215 octets",
    "LONGBLOB" => "4 Go",
    "DATE" => "1000-01-01 à 9999-12-31",
    "DATETIME" => "1000-01-01 00:00:00 à 9999-12-31 23:59:59",
    "TIMESTAMP" => "1970-01-01 00:00:01 UTC à 2038-01-19 03:14:07 UTC",
    "TIME" => "-838:59:59 à 838:59:59",
    "YEAR" => "1901 à 2155",
    "ENUM" => "65 535 valeurs",
    "SET" => "64 éléments maximum"
];
$champs = [];
$champsRequete = ["id INT PRIMARY KEY NOT NULL AUTO_INCREMENT"];

// Ajout de champs automatiques
// Demander à l'utilisateur si il souhaite ajouter automatiquement des champs courants
$champsCourants = [
    "nom" => "nom",
    "prenom" => "prenom",
    "email" => "email",
    "mot de passe (mdp)" => "mdp",
];

echo text("\nSouhaitez-vous ajouter automatiquement les champs suivants (oui par défaut) :\n", "blue");
foreach ($champsCourants as $champ => $label) {
    echo "---------\n$champ ? (".text("y", "yellow")."/n) : ";
    $choixAuto = trim(fgets((STDIN)));
    if ($choixAuto == "" || $choixAuto == "y") {
        $champs[] = $label;
        $champRequete = strtolower($label). " VARCHAR(255)";
        $champsRequete[] = $champRequete;
        echo text("Champ $champ ajouté avec succès\n", "green");
    } elseif ($choixAuto == "n") {
        echo text("!!! Champ $champ non ajouté !!!", "red");
    }
}

// Ajout de champs supplémentaires
$ajouteChamp = true;
while ($ajouteChamp === true) {
    // Demander le nom du champ
    echo "\n\033[34mEntrez le nom du champ à ajouter à l'entités : (Laissez vide pour arrêter l'ajout de champs)\033[0m\n";
    $nomChamp = trim(fgets(STDIN));

    // Si l'utilisateur ne saisit rien, on arrête
    if (empty($nomChamp)) {
        $ajouteChamp = false;
        break;
    }

    $champs[] = $nomChamp;
    $champRequete = strtolower($nomChamp);

    // Choix du type de donnée
    echo text("Type de donnée (? pour la liste des types) :\n", "green");
    // Boucle pour récupérer une entrée valide
    while (true) {
        // Lire l'entrée de l'utilisateur
        $typeChamp = strtoupper(trim(fgets(STDIN)));

        // Si l'utilisateur entre "?", on affiche la liste des types de données
        if ($typeChamp === "?") {
            echo "Types de données (valeur maximum) :\n";
            foreach ($types as $type => $valeurMax) {
                echo "$type ($valeurMax)\n";
            }
            echo text("Veuillez choisir un type de donnée :\n", "green");
        }
        // Si l'entrée correspond à un type valide, on arrête la boucle
        elseif (array_key_exists($typeChamp, $types)) {
            echo text("Vous avez choisi le type : $typeChamp\n", "green");
            break;
        }
        // Si l'entrée n'est pas valide, on demande à nouveau un type de donnée
        else {
            echo text("Type non valide. Essayez à nouveau (? pour la liste des types) :\n", "green");
        }
    }

    echo text("Longueur de la valeur du champ : maximum ".$types[$typeChamp]."\n");
    $length = trim(fgets(STDIN));


    $champRequete .= " " . $typeChamp ."(". $length.")";
    $champsRequete[] = $champRequete;
}

require getcwd().'/config/database.php';

// Connexion à la bdd
try {
    $bdd = new PDO('mysql:host='.DB_INFOS["host"].';dbname='.DB_INFOS["dbname"].';port='.DB_INFOS["port"], DB_INFOS["username"], DB_INFOS["password"]);
    
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = "CREATE TABLE ".strtolower($nomEntite)."(" . implode(",",$champsRequete).")";
    $bdd->exec($sql);
    echo text("La table a été ajouté avec succès.", "green");
} catch (PDOException $e) {
    echo text("Erreur : ".$e->getMessage(), "red");
}

// Fermeture de la connexion
$bdd = null;

$attributsEntite = "private \$id;\n";
$methodesEntite = "
    public function getId(){
		return \$this->id;
	}
";
$listeChamps = "";
foreach ($champs as $champ) {
    // Ajouter l'attribut
    $attribut = "    private $" . lcfirst($champ) .";\n";
    $attributsEntite .= $attribut;

    // Ajouter les méthodes
    $methode = "
    public function get".ucfirst($champ)."(){
        return \$this->".lcfirst($champ).";
    }\n";
    if ($champ !== "mdp") {
    $methode .= "
    public function set".ucfirst($champ)."(\$".lcfirst($champ)."){
        \$this->".lcfirst($champ)." = \$".lcfirst($champ).";
    }\n";
    } else {
    $methode .= "
    public function set".ucfirst($champ)."(\$".lcfirst($champ)."){
        \$this->".lcfirst($champ)." = password_hash(\$".lcfirst($champ).", PASSWORD_DEFAULT);
    }\n";
    }
    $methodesEntite .= $methode;

    // Création du tableau clé(nom du champ) => valeur(valeur du champ)
    $listeChamps .= "\t\t\t'$champ' => " . "\$".strtolower($nomEntite)."->get".ucfirst($champ)."(),\n";
}

// Créer un fichier pour l'entité
$nomTable = strtolower($nomEntite);
$entityClass = <<<ENTITY
<?php

namespace App\Entity;

class {$nomEntite} {

    // Attributs
    {$attributsEntite}

    const TABLE_NAME = '{$nomTable}';

    // Méthodes
    {$methodesEntite}
}
ENTITY;

// Créer un fichier pour l'entityManager
$entityManagerClass = <<<ENTITYMANAGER
<?php

namespace App\Manager;
use App\Entity\{$nomEntite};
use GabriX\AbstractManager;

class {$nomEntite}Manager extends AbstractManager{

    public function find(\$id) {
		return \$this->readOne({$nomEntite}::class, [ 'id' => \$id ]);
	}

    public function findOneBy(\$filters) {
		return \$this->readOne({$nomEntite}::class, \$filters);
	}

    public function findAll() {
		return \$this->readMany({$nomEntite}::class);
	}

    public function findBy(\$filters, \$order = [], \$limit = null, \$offset = null) {
		return \$this->readMany({$nomEntite}::class, \$filters, \$order, \$limit, \$offset);
	}

	public function add({$nomEntite} \${$nomTable}) {
		return \$this->create({$nomEntite}::class, [
{$listeChamps}\t\t]
		);
	}

    public function edit({$nomEntite} \${$nomTable}) {
		return \$this->update({$nomEntite}::class, [
{$listeChamps}\t\t],
			\${$nomTable}->getId()
		);
	}

    public function delete({$nomEntite} \${$nomTable}) {
		return \$this->remove({$nomEntite}::class, \${$nomTable}->getId());
	}

}
ENTITYMANAGER;

// Création de la classe Session
$sessionClass = <<<'SESSION'
<?php
namespace Gabrix;

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

// Modification du fichier Init pour ajout de l'activation de la session
$ajoutActivation = <<<ACTIVATION

\n\nuse GabriX\Session;
// Activation de la session
session::activation();
ACTIVATION;
if (file_put_contents("lib/Init.php", $ajoutActivation, FILE_APPEND) === false) {
    echo text("!!! La modification du fichier Init.php a échoué. Veuillez ajouter session::activation() à la fin du fichier !!!", "red");
}

file_put_contents('src/Entity/'.$nomEntite.'.php', $entityClass);
echo "\n\033[32mL'entité $nomEntite a été créée avec succès dans src/Entity/$nomEntite.php.\033[0m";
file_put_contents('src/Manager/'.$nomEntite.'Manager.php', $entityManagerClass);
echo "\n\033[32mLe manager d'entité $nomEntite a été créée avec succès dans src/Manager/".$nomEntite."Manager.php.\033[0m";
file_put_contents('lib/Session.php', $sessionClass);
echo "\n\033[32mLa class Session a été créée avec succès dans src/Entity/Session.php.\033[0m\n\n";
?>