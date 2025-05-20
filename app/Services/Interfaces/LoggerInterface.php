<?php

namespace App\Services\Interfaces;

interface LoggerInterface
{
    public function log(string $level, string $message): void;
    public function fatal(string $message): void;
    public function critical(string $message): void;
    public function error(string $message): void;
    public function warning(string $message): void;
    public function info(string $message): void;
}
