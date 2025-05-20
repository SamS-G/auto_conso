<?php

namespace App\Core\Traits;

use App\Exceptions\PaginationException;
use App\Services\FileLoggerService;

trait PaginationTrait
{
    protected function initializePaginationTrait(FileLoggerService $fileLoggerService): void
    {
        $this->fileLogger = $fileLoggerService;
    }

    /**
     * Calcule les données de pagination à partir du total d'éléments.
     *
     * @param int $totalItems Nombre total d'éléments à paginer
     * @param int $page Numéro de la page actuelle
     * @param int $itemsPerPage Nombre d'éléments par page (par défaut 10)
     * @return array ['page', 'itemsPerPage', 'offset', 'totalPages']
     * @throws PaginationException Si les arguments ne sont pas valides.
     */
    public function getPaginationData(int $totalItems, int $page, int $itemsPerPage = 10): array
    {
        if ($totalItems < 0) {
            throw new PaginationException(
                $this->fileLogger,
                "Le nombre total d'éléments doit être un entier positif ou nul."
            );
        }
        if ($page < 1) {
            throw new PaginationException(
                $this->fileLogger,
                "Le numéro de page doit être un entier supérieur ou égal à 1."
            );
        }
        if (!is_int($itemsPerPage) || $itemsPerPage < 1) {
            throw new PaginationException(
                $this->fileLogger,
                "Le nombre d'éléments par page doit être un entier supérieur ou égal à 1."
            );
        }

        $page = max(1, $page); // Assure que la page est au moins 1
        $offset = ($page - 1) * $itemsPerPage;
        $totalPages = (int)ceil($totalItems / $itemsPerPage);

        return compact('page', 'itemsPerPage', 'offset', 'totalPages');
    }

    /**
     * Génère une pagination limitée avec ellipses.
     *
     * @param int $currentPage Page active
     * @param int $totalPages Nombre total de pages
     * @param int $maxLinks Nombre de liens visibles de chaque côté (par défaut 2)
     * @return array Tableau de pages à afficher (avec '...' pour ellipses)
     * @throws PaginationException Si les arguments ne sont pas valides.
     */
    public function getLimitedPagination(int $currentPage, int $totalPages, int $maxLinks = 2): array
    {
        if ($currentPage < 1) {
            throw new PaginationException(
                $this->fileLogger,
                "La page actuelle doit être un entier supérieur ou égal à 1."
            );
        }
        if ($totalPages < 1) {
            throw new PaginationException(
                $this->fileLogger,
                "Le nombre total de pages doit être un entier supérieur ou égal à 1."
            );
        }
        if (!is_int($maxLinks) || $maxLinks < 0) {
            throw new PaginationException(
                $this->fileLogger,
                "Le nombre de liens visibles de chaque côté doit être un entier positif ou nul."
            );
        }

        $pages = [];

        if ($totalPages <= ($maxLinks * 2 + 5)) {
            for ($i = 1; $i <= $totalPages; $i++) {
                $pages[] = $i;
            }
            return $pages;
        }

        $pages[] = 1;

        if ($currentPage > $maxLinks + 2) {
            $pages[] = '...';
        }

        $start = max(2, $currentPage - $maxLinks);
        $end = min($totalPages - 1, $currentPage + $maxLinks);

        for ($i = $start; $i <= $end; $i++) {
            $pages[] = $i;
        }

        if ($currentPage < $totalPages - ($maxLinks + 1)) {
            $pages[] = '...';
        }

        return $pages;
    }
}
