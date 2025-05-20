// Centre l'alerte sur la page peux importe où en est le scroll
export const centerRelativeElement = (id) =>
{
    const element = $(id);
    const elementHeight = element.outerHeight();
    const elementWidth = element.outerWidth();

    element.css({
        position: 'fixed',  // Change to fixed instead of absolute
        width: elementWidth + 'px',
        left: '50%',
        marginLeft: -(elementWidth / 2) + 'px',
        top: '50%',
        marginTop: -(elementHeight / 2) + 'px',
        zIndex: 1000
    });
}
// Affiche une alerte d’erreur dans #alert-message et la fait disparaître après 5s
export const showAlert = (id, html) => {
    const alert = $(id)
    alert.html(html);
    centerRelativeElement(alert);
    setTimeout(() => alert.html(''), 5000);
};