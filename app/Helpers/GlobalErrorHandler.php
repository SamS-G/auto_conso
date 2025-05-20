<?php

namespace App\Helpers;

use App\Exceptions\BaseException;
use App\Exceptions\DataBaseException;
use App\Exceptions\NotFoundException;
use App\Exceptions\QueryBuilderException;
use App\Exceptions\ValidationException;
use App\Services\Interfaces\LoggerInterface;
use Error;
use Exception;
use PDOException;
use Throwable;

/**
 * Class qui gère au niveau global de l'application les erreurs / exceptions / shutdowns qui ne seraient pas capturées
 * dans un bloc try / catch
 */
class GlobalErrorHandler
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function createExceptionMessage($exception): string
    {
        if ($exception instanceof BaseException) {
            return json_encode($exception->toArray());
        } elseif ($exception instanceof Exception) {
            return json_encode(
                [
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString()
                ]
            );
        } elseif ($exception instanceof Error) {
            return json_encode(
                [
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'code' => $exception->getCode(),
                    'line' => $exception->getLine(),
                ]
            );
        } else {
            return 'Erreur inattendue';
        }
    }

    /**
     * Gestionnaire d'exceptions global.
     *
     * @param Throwable $exception L'exception a géré.
     */
    public function handleException(Throwable $exception): void
    {
        $message = $this->createExceptionMessage($exception);

        // Détermine le niveau de log en fonction du type d'exception
        if ($exception instanceof ValidationException) {
            $this->logger->warning($message);
        } elseif ($exception instanceof DataBaseException) {
            $this->logger->warning($message);
        } elseif ($exception instanceof QueryBuilderException) {
            $this->logger->error($message);
        } elseif ($exception instanceof NotFoundException) {
            $this->logger->error($message);
        } elseif ($exception instanceof PDOException) {
            $this->logger->critical($message);
        } elseif ($exception instanceof Error) {
            $this->logger->critical($message);
        } elseif ($exception instanceof Exception) {
            $this->logger->error($message);
        } else {
            // Niveau de log par défaut pour les autres types d'exceptions
            $this->logger->fatal($message);
        }

        // Affiche un message d'erreur à l'utilisateur.
        http_response_code(500);
        if (getenv('APP_ENV') === 'production') {
            echo("Une erreur inattendue s'est produite. Veuillez réessayer plus tard.");
        } else {
            // En développement affiche l'exception pour le débogage.
            echo '<prev>';
            print_r($message);
            echo '</prev>';
        }
    }

    /**
     * Gestionnaire d'erreurs global.
     *
     * @param int $errno Le niveau de l'erreur.
     * @param string $errstr Le message d'erreur.
     * @param string $errfile Le nom du fichier où l'erreur s'est produite.
     * @param int $errline Le numéro de la ligne à laquelle l'erreur s'est produite.
     */
    public function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        $errorName = 'Erreur inconnue'; // Valeur par défaut

        switch ($errno) {
            case E_ERROR:
                $errorName = 'E_ERROR';
                break;
            case E_WARNING:
                $errorName = 'E_WARNING';
                break;
            case E_NOTICE:
                $errorName = 'E_NOTICE';
                break;
            case E_PARSE:
                $errorName = 'E_PARSE';
                break;
            case E_CORE_ERROR:
                $errorName = 'E_CORE_ERROR';
                break;
            case E_CORE_WARNING:
                $errorName = 'E_CORE_WARNING';
                break;
            case E_COMPILE_ERROR:
                $errorName = 'E_COMPILE_ERROR';
                break;
            case E_COMPILE_WARNING:
                $errorName = 'E_COMPILE_WARNING';
                break;
            case E_USER_ERROR:
                $errorName = 'E_USER_ERROR';
                break;
            case E_USER_WARNING:
                $errorName = 'E_USER_WARNING';
                break;
            case E_USER_NOTICE:
                $errorName = 'E_USER_NOTICE';
                break;
            case E_STRICT:
                $errorName = 'E_STRICT';
                break;
            case E_RECOVERABLE_ERROR:
                $errorName = 'E_RECOVERABLE_ERROR';
                break;
            case E_DEPRECATED:
                $errorName = 'E_DEPRECATED';
                break;
            case E_USER_DEPRECATED:
                $errorName = 'E_USER_DEPRECATED';
                break;
        }

        $this->logger->error(sprintf(
            "Erreur %s : %s dans %s:%d",
            $errorName,
            $errstr,
            $errfile,
            $errline
        ));

        // Empêche le gestionnaire d'erreurs de PHP de s'exécuter
        return true;
    }

    /**
     * Gestionnaire d'erreurs fatales.
     */
    public function handleFatalError(): void
    {
        $error = error_get_last();
        if ($error !== null && $error['type'] === E_ERROR) {
            // Enregistre l'exception dans un fichier journal.
            $message = sprintf(
                "Erreur fatal : %s dans %s:%d de type: %s",
                $error['message'],
                $error['file'],
                $error['line'],
                $error['type']
            );
            $this->logger->fatal($message);

            // Affiche un message d'erreur à l'utilisateur.
            if (getenv('APP_ENV') === 'production') {
                echo "<pre>";
                print_r("Une erreur inattendue s'est produite. Veuillez réessayer plus tard.");
                echo "<pre>";
            } else {
                // En développement affiche l'exception pour le débogage.
                echo "<pre>";
                print_r($message);
                echo "<pre>";
            }
        }
    }
}
