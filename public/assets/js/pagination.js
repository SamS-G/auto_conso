import {getFormParams, loadVehicles, updateListContent} from './search.js';
import {showAlert} from "./helper.js";

// Pagination AJAX pour la recherche (POST avec critères de recherche)
export const initSearchPagination = () => {
    $(document).on('click', '#pagination .search-page-link', function (e) {
        e.preventDefault();
        const page = $(this).data('page');
        const params = getFormParams();
        params.page = page;
        loadVehicles(params);
    });
};

// Pagination initiale (GET pour affichage global)
export const initDefaultPagination = () => {
    $(document).on('click', '.page-link', function (e) {
        e.preventDefault();
        const page = $(this).data('page');
        $.ajax({
            url: `/vehicules?page=${page}`,
            type: 'GET',
            success: function (html) {
                updateListContent(html);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAlert('#alert-message', jqXHR.responseText);
            }
        })
    });
};
