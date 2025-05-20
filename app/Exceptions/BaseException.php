<?php

namespace App\Exceptions;

use App\Services\FileLoggerService;
use Exception;
use Throwable;

class BaseException extends Exception
{
    private FileLoggerService $logService;

    protected int $statusCode = 500;
    protected string $logLevel = 'ERROR';
    protected array $errorDetails = [];

    public function __construct(
        FileLoggerService $logService,
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null,
        array $errorDetails = []
    ) {
        parent::__construct($message, $code, $previous);

        $this->errorDetails = $errorDetails;
        $this->logService = $logService;

        $this->log();
    }
    protected function setLogLevel(string $level)
    {
        $this->logLevel = $level;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorDetails(): array
    {
        return $this->errorDetails;
    }

    public function setErrorDetails(array $errorDetails): void
    {
        $this->errorDetails = $errorDetails;
    }

    public function getErrorMessage(): string
    {
        return $this->getMessage();
    }

    public function getErrorFile(): string
    {
        return $this->getFile();
    }

    public function getErrorLine(): int
    {
        return $this->getLine();
    }

    public function getErrorTrace(): string
    {
        return $this->getTraceAsString();
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'statusCode' => $this->getStatusCode(),
            'details' => $this->getErrorDetails(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString(),
        ];
    }
    protected function log(): void
    {
        $logMessage = sprintf(
            "%s dans %s:%d\nTrace: %s\nCode: %d\nDetails: %s",
            $this->getMessage(),
            $this->getFile(),
            $this->getLine(),
            $this->getTraceAsString(),
            $this->getCode(),
            json_encode($this->getErrorDetails())
        );

        $this->logService->log($this->logLevel, $logMessage);
    }
}
