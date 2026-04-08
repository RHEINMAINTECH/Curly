<?php
/**
 * AI Provider Interface
 * 
 * @package CurlyCMS\Core\AI
 */

declare(strict_types=1);

namespace CurlyCMS\Core\AI;

interface AIProvider
{
    public function generate(string $systemPrompt, string $userPrompt, array $options = []): array;
    public function isAvailable(): bool;
    public function getModels(): array;
}
