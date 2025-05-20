<?php

namespace App\Services\View\Traits;

use phpQueryObject;

/**
 * Gère le rendu des listes d'éléments HTML dynamiques via phpQuery.
 * Permet de cloner une ligne (template) et d'injecter des données.
 */
trait ListRenderer
{
    /**
     * Rendu générique d'une liste en clonant un élément template.
     *
     * @param phpQueryObject $doc Document phpQuery de base
     * @param array $items Liste d'objets ou tableaux de données
     * @param callable $fillRow Fonction qui reçoit ($row, $item) et injecte les données
     */
    public function renderList(phpQueryObject $doc, array $items, callable $fillRow): void
    {
        $rowTemplate = $doc['.list-item:first']; // classe CSS du template
        $rowContainer = $rowTemplate->parent(); // conteneur de lignes
        $rowTemplate->remove(); // évite duplication visuelle

        foreach ($items as $item) {
            $newRow = $rowTemplate->clone();
            $fillRow($newRow, $item); // appel à la fonction de remplissage personnalisée
            $rowContainer->append($newRow);
        }
    }
}
