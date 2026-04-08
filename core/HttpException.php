<?php
/**
 * HTTP Exception
 * 
 * @package CurlyCMS\Core
 */

declare(strict_types=1);

namespace CurlyCMS\Core;

class HttpException extends \RuntimeException
{
    private int $statusCode;
    private array $headers;

    public function __construct(int $statusCode, string $message = '', ?\Throwable $previous = null, array $headers = [])
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        
        $statusMessages = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            419 => 'Page Expired',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable'
        ];
        
        if (empty($message) && isset($statusMessages[$statusCode])) {
            $message = $statusMessages[$statusCode];
        }
        
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}
