<link href="/assets/css/delete-modal.css" rel="stylesheet">

<div class="modal fade" tabindex="-1" id="confirmDeleteModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer ce véhicule ?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-info" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-sm btn-danger" id="confirmDeleteButton" data-id="<?= $id ?>">
                    Supprimer
                </button>
            </div>
        </div>
    </div>
</div>