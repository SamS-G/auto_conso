<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des véhicules</title>
    <link href="/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/index.css" rel="stylesheet">
</head>
<body>

<div class="container">
    <h1 class="mb-4">Recherche de Véhicules</h1>
    <!-- Formulaire de recherche -->
    <form id="search-form" class="row g-0 mb-4">
        <div class="col-md-3">
            <label for="brand" class="form-label">Marque</label>
            <select id="brand" name="brand" class="form-select">
                <option value="">Toutes</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="energy" class="form-label">Type d'énergie</label>
            <select id="energy" name="energy" class="form-select">
                <option value="">Toutes</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="energyClass" class="form-label">Classe énergétique</label>
            <select id="energyClass" name="energyClass" class="form-select">
                <option value="">Toutes</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="gearbox" class="form-label">Boîte de vitesse</label>
            <select id="gearbox" name="gearbox" class="form-select">
                <option value="">Toutes</option>
            </select>
        </div>
        <div class="d-flex justify-content-between pt-3">
<!--            <button type="submit" class="btn btn-sm btn-primary">Rechercher</button>-->
            <a href="/vehicules?page=1" class="btn btn-sm btn-warning">RAZ filtres</a>
        </div>
    </form>
    <!-- Tableau des résultats -->
    <table class="table table-striped text-center">
        <thead id="table-head">
        <tr>
            <th>Marque</th>
            <th>Modèle</th>
            <th>Transmission</th>
            <th>Énergie</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody id="results-body">
        <tr class="list-item">
            <td class="brand fw-bolder fst-italic"></td>
            <td class="model"></td>
            <td class="transmission"></td>
            <td class="energy"></td>
            <td>
                <button class="btn btn-info btn-sm btn-details" data-id="">Détails</button>
                <button class="btn btn-danger btn-sm btn-delete" data-id="">Supprimer</button>
            </td>
        </tr>
        </tbody>
    </table>
    <!-- Pagination -->
    <div>
        <ul class="pagination justify-content-center" id="pagination"></ul>
    </div>
</div>
<!-- Containers AJAX -->
<div id="modal-container"></div>
<!-- En cas de message d'erreur à afficher -->
<div id="alert-message"></div>
<!-- Spinner -->
<div id="search-spinner"><div id="animation"></div></div>
<script src="/libs/jQuery/jquery-3.7.1.min.js"></script>
<script src="/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/main.js" type="module"></script>
</body>
</html>
