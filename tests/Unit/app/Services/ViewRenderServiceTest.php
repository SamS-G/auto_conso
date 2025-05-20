<?php

namespace Tests\Unit\app\Services;

use App\DataTransferObjects\CarDetailsDTO;
use App\Entities\CarModel;
use App\Exceptions\NotFoundException;
use App\Services\ConfigService;
use App\Services\FileLoggerService;
use App\Services\ViewRenderService;
use PHPUnit\Framework\TestCase;

class ViewRenderServiceTest extends TestCase
{
    private $configService;
    private ViewRenderService $renderer;

    protected function setUp(): void
    {
        $this->configService = $this->createMock(ConfigService::class);
        $fileLogger = $this->createMock(FileLoggerService::class);

        $this->renderer = new ViewRenderService(
            $this->configService,
            $fileLogger
        );
    }
    private function getFakeCarModel(): CarModel
    {
        $car = $this->createMock(CarModel::class);

        $car->method('getId')->willReturn(1);
        $car->method('getModelName')->willReturn('Model X');
        $car->method('getBrandId')->willReturn(1);
        $car->method('getCnit')->willReturn('TES123456');
        $car->method('getEnergyType')->willReturn('ELEC');
        $car->method('getEnergyClass')->willReturn('A');
        $car->method('getGearboxTypeId')->willReturn(2);
        $car->method('getTaxPower')->willReturn(5);
        $car->method('getDinPower')->willReturn(204);
        $car->method('getKwPower')->willReturn(150);

        return $car;
    }

    private function getFakeCarDTO(): CarDetailsDTO
    {
        $fakeCar = $this->getFakeCarModel();

        return new CarDetailsDTO($fakeCar);
    }


    public function testRenderThrowsNotFoundIfTemplateMissing()
    {
        $this->configService->method('get')->willReturn('/path/to/nowhere/index.php');

        $this->expectException(NotFoundException::class);
        $this->renderer->render();
    }

    public function testRenderReturnsHtmlWithMinimalTemplate()
    {
        $file = tempnam(sys_get_temp_dir(), 'tmpl');
        file_put_contents($file, '<html><body><div id="brand"></div></body></html>');

        $this->configService->method('get')->willReturn($file);

        $html = $this->renderer->render(['brands' => ['Peugeot']]);
        $this->assertStringContainsString('<html>', $html);

        unlink($file);
    }

    public function testRenderCarDetailsPopupThrowsNotFound()
    {
        $this->expectException(NotFoundException::class);

        $dto = $this->getFakeCarDTO();

        $this->configService
            ->method('get')
            ->with('app.templates_path.details')
            ->willReturn('/invalid/path/details.php');

        $this->renderer->renderCarDetailsPopup($dto);
    }

    public function testRenderAlertReturnsHtml()
    {
        $file = tempnam(sys_get_temp_dir(), 'alert');
        file_put_contents($file, '<div id="alert"></div>');

        $this->configService->method('get')->willReturn($file);

        $html = $this->renderer->renderAlert(['type' => 0, 'debug' => 'Info']);
        $this->assertStringContainsString('<div', $html);

        unlink($file);
    }

    public function testRenderEnergyLabelReturnsValidHtml()
    {
        $label = $this->renderer->renderEnergyLabel('B');
        $this->assertStringContainsString('energy-B', $label);

        $labelInvalid = $this->renderer->renderEnergyLabel('Z');
        $this->assertStringContainsString('?', $labelInvalid);
    }
}
