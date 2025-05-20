<?php

namespace App\Http\Controllers;

use App\Core\Traits\PaginationTrait;
use App\Exceptions\DataBaseException;
use App\Exceptions\DTOException;
use App\Exceptions\NotFoundException;
use App\Exceptions\PaginationException;
use App\Exceptions\RenderException;
use App\Exceptions\ValidationException;
use App\Services\CarModelService;
use App\Services\FileLoggerService;
use App\Services\ViewRenderService;
use RuntimeException;

class CarModelController
{
    use PaginationTrait;

    protected ?FileLoggerService $fileLogger;
    private ViewRenderService $render;
    private CarModelService $carModelService;

    public function __construct(
        CarModelService $carModelService,
        ViewRenderService $render,
        FileLoggerService $fileLogger
    ) {
        $this->carModelService = $carModelService;
        $this->render = $render;
        $this->fileLogger = $fileLogger;
        $this->initializePaginationTrait($this->fileLogger);
    }

    /**
     * @throws NotFoundException|DTOException|RenderException
     */
    public function index()
    {
        try {
            $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
            $totalItems = $this->carModelService->getTotalItems('car_model');
            $pagination = $this->getPaginationData($totalItems, $page);

            // Jointure
            $joins = ['brand_id' => ['table' => 'brand', 'display' => ['brand_name']]];

            // Récupération de la liste de véhicules en DTOCarDetails
            $cars = $this->carModelService->getAllCarsModels(
                ['id', 'model_name', 'energy_type', 'gearbox_type_id'],
                'brand_id',
                'ASC',
                $joins,
                $pagination['offset'],
                $pagination['itemsPerPage']
            );

            // Valeur des selects du formulaire de recherche depuis les ENUMS
            $tableEnums = $this->carModelService->getEnumValues([
                'brand' => ['id', 'brand_name'],
                'gearbox_type' => ['id', 'transmission']
            ]);
            $columnEnum = $this->carModelService->getEnumValues([
                'car_model' => ['energy_type', 'energy_class'],
            ], false);


            // Envoi des données pour générer la vue
            echo $this->render->render([
                'cars' => $cars,
                'brands' => $tableEnums['brand'],
                'gearboxes' => $tableEnums['gearbox_type'],
                'energies' => $columnEnum['car_model']['energy_type'],
                'energyClass' => $columnEnum['car_model']['energy_class'],
                'pagination' => [
                    'currentPage' => $pagination['page'],
                    'visibleLinks' => $this->getLimitedPagination($pagination['page'], $pagination['totalPages'], 10),
                    'isSearch' => false
                ],
                'currentPage' => $pagination['page']
            ]);
        } catch (NotFoundException | PaginationException | DataBaseException | RenderException $e) {
            http_response_code(500);
            echo $this->render->renderAlert(
                [
                    'type' => 1,
                    'message' => 'Une erreur inattendue est survenue',
                    'debug' => $e->getMessage()
                ]
            );
        }
        exit();
    }

    /**
     * Affiches les détails de consommation et caractéristiques du véhicule sélectionné
     * @throws NotFoundException|RenderException
     */
    public function details()
    {
        try {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            $carDetails = $this->carModelService->getCarDetailsById($id);

            if (!empty($carDetails)) {
                echo $this->render->renderCarDetailsPopup($carDetails);
            } else {
                echo $this->render->renderAlert(
                    ['type' => 1,
                        'message' => 'Oups nous n\'avons pas pu récupérer les données de ce véhicule !'
                    ]
                );
            }
        } catch (NotFoundException | RuntimeException | RenderException $e) {
            echo $this->render->renderAlert(['type' => 1, 'message' => 'Une erreur inattendue est survenue']);
        }

        header('Content-Type: text/html');
        exit();
    }

    /**
     * Recherche de véhicules en filtrant avec 1 à 4 paramètres
     *
     * @throws NotFoundException|RenderException
     */
    public function search()
    {
        $page = filter_input(INPUT_POST, 'page', FILTER_VALIDATE_INT) ?: 1;

        $filters = [
            'brand_id' => $_POST['brand'] ?? null,
            'energy_type' => $_POST['energy'] ?? null,
            'energy_class' => $_POST['energyClass'] ?? null,
            'gearbox_type_id' => $_POST['gearbox'] ?? null,
        ];

        try {
            $this->carModelService->validateSearchFormData($filters);

            $totalItems = $this->carModelService->getTotalFilteredItems('car_model', $filters);
            $pagination = $this->getPaginationData($totalItems, $page);

            $cars = $this->carModelService->searchVehicles(
                $filters,
                $pagination['itemsPerPage'],
                $pagination['offset'],
            );
            if (!empty($cars)) {
                http_response_code(200);
                echo $this->render->render(
                    [
                        'cars' => $cars,
                        'pagination' => [
                            'currentPage' => $pagination['page'],
                            'visibleLinks' => $this->getLimitedPagination(
                                $pagination['page'],
                                $pagination['totalPages'],
                                10
                            ),
                            'isSearch' => true
                        ],
                        'currentPage' => $pagination['page']
                    ]
                );
            } else {
                echo $this->render->render($cars);
            }
        } catch (
            ValidationException
            | DTOException
            | NotFoundException
            | DataBaseException
            | PaginationException
            | RenderException
            $ex
        ) {
            http_response_code(500);
            echo $this->render->renderAlert(
                [
                    'type' => 1,
                    'message' => 'Une erreur inattendue est survenue',
                    'debug' => $ex->getMessage()
                ]
            );
        }
        exit();
    }

    /**
     * Appel la fenêtre de confirmation de suppression d'un élément
     *
     * @return void
     * @throws NotFoundException|RenderException
     */
    public function deleteConfirm()
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $html = $this->render->renderDeleteConfirmationPopup($id);
        header('Content-Type: text/html');
        echo $html;
        exit();
    }

    /**
     * Effectue la suppression suite à la confirmation
     *
     * @throws NotFoundException|RenderException
     */
    public function delete()
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: null;

        try {
            if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                $delete = $this->carModelService->deleteCar($id);
            }
            if (isset($delete) && $delete) {
                $html = $this->render->renderAlert(['type' => 2, 'message' => 'Le véhicule a été supprimé.']);
            } else {
                $html = $this->render->renderAlert(
                    [
                        'type' => 1, 'message' => 'Une erreur inattendue est survenue',
                        'debug' => 'Erreur lors de l\'exécution de DELETE sur la DB'
                    ]
                );
            }
        } catch (NotFoundException $e) {
            $html = $this->render->renderAlert(
                [
                    'type' => 1,
                    'message' => 'Une erreur inattendue est survenue',
                    'debug' => $e->getMessage()
                ]
            );
        }

        header('Content-Type: text/html');
        echo $html;
        exit();
    }
}
