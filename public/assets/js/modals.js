import {updateListContent} from './search.js';
import {showAlert} from './helper.js';

export const initModals = () => {
    // Ouvre la modale des détails
    $(document).on('click', '.btn-details', function () {
        const id = $(this).data('id');
        $.get(` /vehicules/details?id=${id}`, function (html) {
            const $html = $(html);
            const containsError = $html.find('#alertModal').length > 0 || html.includes('erreur') || html.includes('Erreur');
            containsError
                ? showAlert('#alert-message', html)
                : updateModelContent(html);
        });
    });
    const updateModelContent = (html) => {
        $('#modal-container').html(html);
        new bootstrap.Modal('#detailsModal').show();
    }

    // Ouvre la modale de confirmation de suppression
    $(document).on('click', '.btn-delete', function () {
        const id = $(this).data('id');
        $.get(` /vehicules/delete-confirm?id=${id}`, function (html) {
            $('#modal-container').html(html);
            new bootstrap.Modal('#confirmDeleteModal').show();
        });
    });
    // Confirmation finale de suppression
    $(document).on('click', '#confirmDeleteButton', function () {
        const id = $(this).data('id');
        $.ajax({
            url: ` /vehicules/delete?id=${id}`,
            method: 'DELETE',
            success: function (html) {
                // MAJ de la vue
                $.get($(location).prop('href'))
                    .done(function (html) {
                        updateListContent(html);
                    })
                    .fail(function (jqXHR) {
                        showAlert('#alert-message', jqXHR.responseText)
                    });
                // Fermeture de la modale, message de confirmation
                bootstrap.Modal.getInstance(document.getElementById('confirmDeleteModal')).hide();
                showAlert('#alert-message', html)
            },
            fail: function (jqXHR) {
                showAlert('#alert-message', jqXHR.responseText)
            }
        });
    });
};
