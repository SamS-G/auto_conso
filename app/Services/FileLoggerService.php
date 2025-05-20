<?php

namespace App\Services;

use App\Services\Interfaces\LoggerInterface;

class FileLoggerService implements LoggerInterface
{
    private ConfigService $configService;
    private string $filePath;

    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
        $this->filePath = $this->configService->get('app.logFilePath', '/tmp/app.log');

        if (!file_exists($this->filePath)) {
            fopen($this->filePath, 'c+b');
        }
    }

    public function log(string $level, string $message): void
    {
        $date = date('Y-m-d H:i:s');
        $logMessage = sprintf("[%s] [%s] %s\n", $date, $level, $message);
        file_put_contents($this->filePath, $logMessage, FILE_APPEND);
    }

    public function fatal(string $message): void
    {
        $this->log('FATAL', $message);
    }

    public function error(string $message): void
    {
        $this->log('ERROR', $message);
    }

    public function warning(string $message): void
    {
        $this->log('WARNING', $message);
    }

    public function info(string $message): void
    {
        $this->log('INFO', $message);
    }

    public function critical(string $message): void
    {
        $this->log('CRITICAL', $message);
    }
}
