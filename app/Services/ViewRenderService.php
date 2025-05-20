<?php

namespace App\Services;

use App\DataTransferObjects\CarDetailsDTO;
use App\Exceptions\NotFoundException;
use App\Exceptions\RenderException;
use App\Services\View\Traits\ListRenderer;
use App\Services\View\Traits\PaginationRenderer;
use App\Services\View\Traits\PopupRenderer;
use App\Services\View\Traits\SelectRenderer;
use phpQuery;
use phpQueryObject;
use Throwable;

/**
 * Service central de rendu HTML utilisant phpQuery.
 * Il orchestre l'affichage des listes, formulaires, popups et pagination.
 */
class ViewRenderService
{
    use ListRenderer;
    use PaginationRenderer;
    use SelectRenderer;
    use PopupRenderer;

    protected ConfigService $configService;
    protected FileLoggerService $fileLogger;
    public function __construct(
        ConfigService $configService,
        FileLoggerService $fileLogger
    ) {

        $this->configService = $configService;

        $this->fileLogger = $fileLogger;
    }


    /**
     * Rend une page principale avec injection des données dynamiques.
     *
     * @param array $data Tableau des données à afficher
     * @return string
     * @throws NotFoundException
     * @throws RenderException
     */
    public function render(array $data = []): string
    {
        $templateFile = rtrim($this->configService->get('app.templates_path.index'), '/');

        if (!file_exists($templateFile)) {
            throw new NotFoundException($this->fileLogger, "Template not found: {$templateFile}");
        }

        ob_start();
        extract($data);
        include $templateFile;

        $html = ob_get_clean();
        try {
            $doc = phpQuery::newDocumentHTML($html);

            if (empty($data)) {
                $this->renderIsEmpty($doc);
            }
            // Injection dynamique dans les <select>
            if (isset($data['brands'])) {
                $this->populateSelectOptions($doc['#brand'], $data['brands']);
            }
            if (isset($data['energies'])) {
                $this->populateSelectOptions($doc['#energy'], $data['energies']);
            }
            if (isset($data['gearboxes'])) {
                $this->populateSelectOptions($doc['#gearbox'], $data['gearboxes']);
            }
            if (isset($data['energyClass'])) {
                $this->populateSelectOptions($doc['#energyClass'], $data['energyClass']);
            }
            // Rendu de la liste des véhicules
            if (isset($data['cars'])) {
                $this->renderList($doc, $data['cars'], function ($row, $car) {
                    $row->find('.brand')->text($car->brandName);
                    $row->find('.model')->text($car->modelName);
                    $row->find('.transmission')->attr('data-value', $car->transmission);
                    $row->find('.energy')->attr('data-value', $car->energyType);
                    $row->find('.btn-details')->attr('data-id', $car->id);
                    $row->find('.btn-delete')->attr('data-id', $car->id);
                });
            }

            // Rendu de la pagination si présente
            if (isset($data['pagination'])) {
                $this->renderPagination($doc, $data['pagination'], $data['currentPage'] ?? 1);
            }
            // Récupération et envoi du HTML généré
            return $doc->htmlOuter();
        } catch (Throwable $e) {
            throw new RenderException($this->fileLogger, $e->getMessage(), [], $e->getCode(), $e);
        }
    }

    /**
     * Affiche un message/visuel si la liste est vide.
     *
     * @param phpQueryObject $doc L'élément sur lequel rendre le HTML
     * @return void
     * @throws RenderException
     */
    private function renderIsEmpty(phpQueryObject $doc): void
    {
        try {
            $doc->find('body > div.container > table > thead > tr')->remove();
            $tr = $doc->find('tr.list-item');
            $tr->html('<td colspan="4" class="text-center">
        <img src="/assets/img/empty_search.png" alt="Aucune donnée" class="img-fluid"/></td>');
        } catch (Throwable $e) {
            throw new RenderException($this->fileLogger, $e->getMessage(), [], $e->getCode(), $e);
        }
    }

    /**
     * Affiche un message/visuel si la liste est vide.
     *
     * @param CarDetailsDTO $car Les détails à afficher
     * @return string
     * @throws NotFoundException
     * @throws RenderException
     */
    public function renderCarDetailsPopup(CarDetailsDTO $car): string
    {

        $templateFile = rtrim($this->configService->get('app.templates_path.details'), '/');
        $energyClass = $this->renderEnergyLabel($car->energyClass);

        try {
            return $this->renderDetailsPopup($templateFile, $car, ['formatedEnergyClass' => $energyClass]);
        } catch (NotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new RenderException($this->fileLogger, $e->getMessage(), [], $e->getCode(), $e);
        }
    }

    /**
     * Rend un popup de confirmation de suppression
     *
     * @param int $carId L'idée de l'élément à supprimer
     * @return string
     * @throws NotFoundException
     * @throws RenderException
     */
    public function renderDeleteConfirmationPopup(int $carId): string
    {
        $templateFile = rtrim($this->configService->get('app.templates_path.delete'), '/');

        try {
            return $this->renderDeletePopup($templateFile, $carId);
        } catch (NotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new RenderException($this->fileLogger, $e->getMessage(), [], $e->getCode(), $e);
        }
    }

    /**
     * Rend une alerte temporaire (ex: message d'erreur)
     *
     * @param array $alert
     * @return string
     * @throws NotFoundException
     * @throws RenderException
     */
    public function renderAlert(array $alert): string
    {
        if ($alert['type'] === 1) {
            $this->fileLogger->critical($alert['debug'] ?? 'Une erreur inattendue est survenue');
        }
        $templateFile = rtrim($this->configService->get('app.templates_path.alert'), '/');

        try {
            return $this->renderAlertMessage($templateFile, $alert);
        } catch (NotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new RenderException($this->fileLogger, $e->getMessage(), [], $e->getCode(), $e);
        }
    }


    /**
     * Rend un label de classe énergétique coloré (ex: <span>A</span>)
     * @throws RenderException
     */
    public function renderEnergyLabel(string $energyClass): string
    {
        $validClasses = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        $energyClass = strtoupper($energyClass);

        try {
            if (!in_array($energyClass, $validClasses)) {
                return '<span class="energy-class bg-secondary">?</span>';
            }
            return sprintf(
                '<span class="energy-class energy-%s">%s</span>',
                $energyClass,
                $energyClass
            );
        } catch (Throwable $e) {
            throw new RenderException($this->fileLogger, $e->getMessage(), [], $e->getCode(), $e);
        }
    }
}
