import {getFormParams, loadVehicles} from './search.js';
import {initDefaultPagination, initSearchPagination} from './pagination.js';
import {initModals} from './modals.js';

$(function () {
    let searchTimer;
    // Écouteur d'événement sur les champs de saisie du formulaire
    $('#search-form select').on('input change', function () {
        // Annuler le timer précédent s'il existe
        clearTimeout(searchTimer);
        $('#search-spinner').show()
        // Définir un nouveau timer
        searchTimer = setTimeout(function () {
            const params = getFormParams();
            params.page = 1;
            loadVehicles(params);
        }, 500);
    });

    // Gestion de la recherche (formulaire principal) avec bouton search

    // $('#search-form').on('submit', function (e) {
    //     e.preventDefault();
    //     clearTimeout(searchTimer); // Annuler le timer en cours s'il existe
    //     const params = getFormParams();
    //     params.page = 1;
    //     loadVehicles(params);
    // });

    // Initialisation
    initDefaultPagination();
    initSearchPagination();
    initModals();
});
