<?php

namespace App\Entities;

class GearboxType extends AbstractEntity
{
    public static string $table = 'gearbox_type';
    protected ?string $transmission;
    public function getTransmission(): string
    {
        return $this->transmission;
    }

    public function setTransmission(string $transmission): void
    {
        $this->transmission = $transmission;
    }
}
