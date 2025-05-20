<link href="/assets/css/details-modal.css" rel="stylesheet">

<div class="modal fade show" id="detailsModal" tabindex="-1" aria-modal="true" role="dialog" style="display: block;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails du véhicule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="details-group">
                    <p><strong>Modèle :</strong><?= $data->modelName ?? 'N/A' ?></p>
                    <p><strong>®️ Marque :</strong><?= $data->brandName ?? 'N/A' ?></p>
                    <p><strong>🔋 Énergie :</strong><?= $data->energyType ?? 'N/A' ?></p>
                    <p><strong>⚙️ Transmission :</strong><?= $data->transmission ?? 'N/A' ?></p>
                    <p><strong>🆔 CNIT :</strong><?= $data->cnit ?? 'N/A' ?></p>
                    <p><strong>⚡ Puissance fiscale :</strong><?= $data->taxPower ?? 'N/A' ?></p>
                </div>
                <div class="details-group">
                    <p><strong>⚡ Puissance DIN :</strong><?= $data->dinPower ?? 'N/A' ?></p>
                    <p><strong>⚡ Puissance Kw :</strong><?= $data->kwPower ?? 'N/A' ?></p>
                    <p><strong>⛽ Consommation urbaine :</strong><?= $data->cityConsumption ?? 'N/A' ?> L/100km</p>
                    <p><strong>⛽ Consommation extra-urbaine :</strong><?= $data->extraCityConsumption ?? 'N/A' ?>
                        L/100km
                    </p>
                    <p><strong>⛽ Consommation mixte :</strong><?= $data->mixedConsumption ?? 'N/A' ?> L/100km</p>
                    <p><strong>Classe énergétique :</strong><?= $data->formatedEnergyClass ?? 'N/A' ?></p>
                </div>
            </div>
        </div>
    </div>
</div>