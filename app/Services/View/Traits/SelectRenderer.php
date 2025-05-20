<?php

namespace App\Services\View\Traits;

use phpQueryObject;

/**
 * Gère le remplissage dynamique des balises <select> avec des options.
 */
trait SelectRenderer
{
    /**
     * Injecte des <option> dans un élément <select> à partir d'un tableau associatif.
     *
     * @param phpQueryObject $select L'élément <select> dans lequel injecter
     * @param array $options Un tableau de clé → valeur.
     * @param string|null $selected Valeur à présélectionner
     */
    public function populateSelectOptions(phpQueryObject $select, array $options, ?string $selected = null): void
    {
        foreach ($options as $label => $value) {
            $option = pq('<option>')->val($value)->text($label);

            if ($selected !== null && $value == $selected) {
                $option->attr('selected', 'selected');
            }

            $select->append($option);
        }
    }
}
