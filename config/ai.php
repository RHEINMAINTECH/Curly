<?php
/**
 * AI Configuration
 */

return [
    'provider' => getenv('AI_PROVIDER') ?: 'openai',
    'api_key' => getenv('AI_API_KEY') ?: '',
    'model' => getenv('AI_MODEL') ?: 'gpt-4',
    'max_tokens' => (int) (getenv('AI_MAX_TOKENS') ?: 4096),
    'temperature' => (float) (getenv('AI_TEMPERATURE') ?: 0.7),
    'timeout' => 120,
    
    'features' => [
        'content_generation' => true,
        'seo_optimization' => true,
        'translation' => true,
        'image_descriptions' => true,
        'content_analysis' => true
    ],
    
    'templates_path' => CMS_STORAGE . '/ai/templates',
    
    'default_options' => [
        'temperature' => 0.7,
        'max_tokens' => 2000
    ]
];
