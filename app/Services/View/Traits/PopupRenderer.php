<?php

namespace App\Services\View\Traits;

use App\Exceptions\NotFoundException;

/**
 * Gère le rendu de popups dynamiques à partir de templates.
 * Utilise output buffering et injection de données.
 */
trait PopupRenderer
{
    /**
     * Rend un popup de détails à partir d'un template et d'un objet.
     *
     * @param string $templateFile Chemin absolu du fichier PHP
     * @param object $data L'objet de données (par ex. un modèle de voiture)
     * @return string HTML rendu
     * @throws NotFoundException
     */
    public function renderDetailsPopup(string $templateFile, object $data, array $formatedData = null): string
    {
        if (!file_exists($templateFile)) {
            throw new NotFoundException($this->fileLogger, "Template not found: $templateFile");
        }

        // Extraction de l'objet pour être accessible sous forme de variables
        ob_start();

        if ($formatedData) {
            foreach ($formatedData as $key => $value) {
                $data->{$key} = $value;
            }
        }
        include $templateFile;
        return ob_get_clean();
    }

    /**
     * Rend un popup de confirmation de suppression.
     *
     * @param string $templateFile
     * @param int|string $id Identifiant à insérer dans le template
     * @return string
     * @throws NotFoundException
     */
    public function renderDeletePopup(string $templateFile, $id): string
    {
        if (!file_exists($templateFile)) {
            throw new NotFoundException($this->fileLogger, "Template not found: {$templateFile}");
        }

        ob_start();
        $carId = $id;
        include $templateFile;
        return ob_get_clean();
    }

    /**
     * Rend une alerte ou un message temporaire via template.
     *
     * @param string $templateFile
     * @param array $alert
     * @return string
     * @throws NotFoundException
     */
    public function renderAlertMessage(string $templateFile, array $alert): string
    {
        if (!file_exists($templateFile)) {
            throw new NotFoundException($this->fileLogger, "Template not found: {$templateFile}");
        }

        ob_start();
        include $templateFile;
        return ob_get_clean();
    }
}
