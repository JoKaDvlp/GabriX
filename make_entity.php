<?php

include "colors.php";

// Message d'accueil de l'ajout d'entité
echo "\n\033[96m\033[43m--- Ajout d'une entité à votre projet ---\033[0m\n\n";

// Demander le nom de l'entité
echo "\033[34mEntrez le nom de l'entité à créer : \033[0m";
$nomEntite = ucfirst(trim(fgets(STDIN)));

// Création des champs de l'entité
// Tant que $nomChamp est différent de 0, je demande à ajouter un nouveau champ
$ajouteChamp = true;
$champs = [];
while ($ajouteChamp === true) {
    // Demander le nom du champ
    echo "\033[34mEntrez le nom du champ à ajouter à l'entités : (Laissez vide pour arrêter l'ajout de champs)\033[0m\n";
    $nomChamp = trim(fgets(STDIN));

    // Si l'utilisateur ne saisit rien, on arrête
    if (empty($nomChamp)) {
        $ajouteChamp = false;
        break;
    }

    // Ajouter le champ à la liste
    $champs[] = $nomChamp;
}

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
    }\n
    public function set".ucfirst($champ)."(\$".lcfirst($champ)."){
        \$this->".lcfirst($champ)." = \$".lcfirst($champ).";
    }\n";
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
use App\Entity\Article;
use GabriX\Manager\AbstractManager;

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

file_put_contents('src/Entity/'.$nomEntite.'.php', $entityClass);
echo "\n\033[32mL'entité $nomEntite a été créée avec succès dans src/Entity/$nomEntite.php.\033[0m";
file_put_contents('src/Manager/'.$nomEntite.'Manager.php', $entityManagerClass);
echo "\n\033[32mLe manager d'entité $nomEntite a été créée avec succès dans src/EManager/$nomEntite\Manager.php.\033[0m\n\n";