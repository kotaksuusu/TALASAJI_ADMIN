<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Talasaji API',
    description: 'API Documentation for Talasaji Application',
    contact: new OA\Contact(email: 'admin@talasaji.com')
)]
#[OA\Server(
    url: 'http://localhost:8000',
    description: 'Local Development Server'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum',
    description: 'Enter your Bearer token'
)]
class SwaggerController
{
}
