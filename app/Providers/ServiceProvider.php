<?php

namespace App\Providers;

use App\Core\DependencyInjector;
use App\Core\Router;
use App\Database\DatabaseSeeders;
use App\Database\QueryBuilder;
use App\Exceptions\BaseException;
use App\Helpers\GlobalErrorHandler;
use App\Http\Controllers\CarModelController;
use App\Http\Controllers\SeedController;
use App\Repositories\BrandRepository;
use App\Repositories\CarModelRepository;
use App\Repositories\GearboxTypeRepository;
use App\Repositories\Interfaces\CarModelRepositoryInterface;
use App\Repositories\Interfaces\RepositoryInterface;
use App\Services\CarModelService;
use App\Services\ConfigService;
use App\Services\DTOCarFactoryService;
use App\Services\FileLoggerService;
use App\Services\Interfaces\LoggerInterface;
use App\Services\PdoService;
use App\Services\ViewRenderService;
use App\Validation\CarModelSearchValidator;

class ServiceProvider
{
    public function register(DependencyInjector $injector)
    {
        $configPath = __DIR__ . '/../../config';

        $injector->register(Router::class, function () use ($injector) {
            return new Router(
                $injector->resolve(ConfigService::class),
                $injector,
            );
        }, true);
        $injector->register(BaseException::class, function () use ($injector) {
            return new BaseException($injector->resolve(LoggerInterface::class, 'logger.file'));
        });
        $injector->register(GlobalErrorHandler::class, function () use ($injector) {
            return new GlobalErrorHandler($injector->resolve(LoggerInterface::class, 'logger.file'));
        }, true);
        $injector->register(CarModelSearchValidator::class, function () use ($injector) {
            return new CarModelSearchValidator(
                $injector->resolve(RepositoryInterface::class, 'repository.brand'),
                $injector->resolve(RepositoryInterface::class, 'repository.gearboxType'),
                $injector->resolve(LoggerInterface::class, 'logger.file'),
            );
        });
// REPOSITORIES
        $injector->register(CarModelRepositoryInterface::class, function () use ($injector) {
            return new CarModelRepository(
                $injector->resolve(PdoService::class),
                $injector->resolve(QueryBuilder::class),
                $injector->resolve(LoggerInterface::class, 'logger.file')
            );
        }, false, 'repository.carModel');
        $injector->register(RepositoryInterface::class, function () use ($injector) {
            return new BrandRepository(
                $injector->resolve(PdoService::class),
                $injector->resolve(QueryBuilder::class),
                $injector->resolve(LoggerInterface::class, 'logger.file')
            );
        }, false, 'repository.brand');
        $injector->register(RepositoryInterface::class, function () use ($injector) {
            return new GearboxTypeRepository(
                $injector->resolve(PdoService::class),
                $injector->resolve(QueryBuilder::class),
                $injector->resolve(LoggerInterface::class, 'logger.file')
            );
        }, false, 'repository.gearboxType');

// DATABASE
        $injector->register(QueryBuilder::class, function () use ($injector) {
            return new QueryBuilder($injector->resolve(FileLoggerService::class));
        });
        $injector->register(DatabaseSeeders::class, function () use ($injector) {
            return new DatabaseSeeders(
                $injector->resolve(PdoService::class)->getConnection(),
                $injector->resolve(LoggerInterface::class, 'logger.file'),
                $injector->resolve(ConfigService::class),
            );
        });
// SERVICES
        $injector->register(ConfigService::class, function () use ($configPath) {
            return new ConfigService($configPath);
        }, true);
        $injector->register(LoggerInterface::class, function () use ($injector) {
            return new FileLoggerService($injector->resolve(ConfigService::class));
        }, true, 'logger.file');

        $injector->register(PdoService::class, function () use ($injector) {
            return new PdoService(
                $injector->resolve(ConfigService::class),
                $injector->resolve(LoggerInterface::class, 'logger.file'),
            );
        }, true);

        $injector->register(ViewRenderService::class, function () use ($injector) {
            return new ViewRenderService(
                $injector->resolve(ConfigService::class),
                $injector->resolve(LoggerInterface::class, 'logger.file'),
            );
        });
        $injector->register(CarModelService::class, function () use ($injector) {
            return new CarModelService(
                $injector->resolve(CarModelRepositoryInterface::class, 'repository.carModel'),
                $injector->resolve(CarModelSearchValidator::class),
                $injector->resolve(DTOCarFactoryService::class),
            );
        });
        $injector->register(DTOCarFactoryService::class, function () use ($injector) {
            return new DTOCarFactoryService($injector->resolve(LoggerInterface::class, 'logger.file'));
        });

// CONTROLLERS
        $injector->register(SeedController::class, function () use ($injector) {
            return new SeedController(
                $injector->resolve(DatabaseSeeders::class),
                $injector->resolve(LoggerInterface::class, 'logger.file')
            );
        });
        $injector->register(CarModelController::class, function () use ($injector) {
            return new CarModelController(
                $injector->resolve(CarModelService::class),
                $injector->resolve(ViewRenderService::class),
                $injector->resolve(LoggerInterface::class, 'logger.file'),
            );
        });
    }
}
