<?php

namespace App\Services\View\Traits;

use phpQueryObject;

/**
 * Fournit une méthode générique pour rendre une pagination dynamique Bootstrap.
 */
trait PaginationRenderer
{
    /**
     * Gère l'affichage de la pagination.
     *
     * @param phpQueryObject $doc
     * @param array $paginationData Doit contenir 'visibleLinks', 'isSearch'
     * @param int $currentPage
     */
    public function renderPagination(phpQueryObject $doc, array $paginationData, int $currentPage): void
    {
        $pagination = $doc['#pagination'];
        $pagination->empty();

        $visibleLinks = $paginationData['visibleLinks'] ?? [];
        $isSearch = $paginationData['isSearch'] ?? false;
        $baseUrl = $_SERVER['PHP_SELF'];

        // Lien précédent
        $pagination->append($this->createPaginationLink($currentPage - 1, $currentPage, $isSearch, $baseUrl, 'prev'));

        // Pages intermédiaires
        foreach ($visibleLinks as $page) {
            if ($page === '...') {
                $ellipsis = pq('<li class="page-item disabled"><span class="page-link">...</span></li>');
                $pagination->append($ellipsis);
            } else {
                $pagination->append($this->createPaginationLink($page, $currentPage, $isSearch, $baseUrl));
            }
        }

        // Lien suivant
        $pagination->append($this->createPaginationLink($currentPage + 1, $currentPage, $isSearch, $baseUrl, 'next'));
    }

    /**
     * Gère la création d'un lien <li><a></a></li> Bootstrap
     *
     * @param int $page Numéro de la page
     * @param int $currentPage Page courante affichée
     * @param bool $isSearch Précise si c'est une pagination globale ou sur une recherche
     * @param string $baseUrl Url de base à intégrer sur le lien
     * @param string $type Numéro de page, flèche next ou previous
     * @return phpQueryObject
     */
    private function createPaginationLink(
        int $page,
        int $currentPage,
        bool $isSearch,
        string $baseUrl,
        string $type = 'page'
    ): phpQueryObject {
        $icon = $type === 'prev'
            ? '&laquo;'
            : ($type === 'next' ? '&raquo;' : null);

        $text = $icon ?: $page; // Soit icon, soit page.
        $class = $isSearch ? 'search-page-link' : 'page-link';

        $a = pq("<a class=\"$class\">$text</a>");
        $a->attr('data-page', $page);
        $a->attr('href', $baseUrl . '?page=' . $page);

        $li = pq('<li class="page-item"></li>')->append($a);

        if ((int)$page === (int)$currentPage && $type === 'page') {
            $li->addClass('active');
        }

        return $li;
    }
}
