import {showAlert} from './helper.js';
// Récupère les valeurs du formulaire de recherche
export const getFormParams = () => {
    return $('#search-form').serializeArray().reduce((acc, {name, value}) => {
        // Pour chaque champ du formulaire, on remplit un objet {name: value}
        acc[name] = value;
        return acc;
    }, {});
};
// Met à jour dynamiquement les sections HTML avec les résultats retournés
export const updateListContent = (html) => {
    const $html = $(html);
    $('#results-body').html($html.find('#results-body').html());
    $('#pagination').html($html.find('#pagination').html());
    $('#table-head').html($html.find('#table-head').html());
};

// Envoie une requête POST pour charger les véhicules (recherche ou pagination)
export const loadVehicles = (params = {}) => {
    $.ajax({
        url: '/vehicules/search',
        method: 'POST',
        data: params,
        success: function (response) {
            updateListContent(response);
        },
        error: function (response) {
            showAlert('#alert-message', response.responseText)
        }
    }, $('#search-spinner').hide())
};
