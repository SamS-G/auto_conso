<link href="/assets/css/alert.css" rel="stylesheet">

<div id="alertModal" class="container alert <?= $alert['type'] === 1 ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show pt-1 pb-1 text-center" role="alert">
     <?= $alert['message'] ?>
    <button type="button" class="btn-close pt-3 pb-0" data-bs-dismiss="alert" aria-label="Fermer"></button>
</div>