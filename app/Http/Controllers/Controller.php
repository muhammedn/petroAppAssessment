<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'PetroApp Transfer Events API',
    version: '1.0.0',
    description: 'API for managing transfer events'
)]
#[OA\Server(
    url: '/api',
    description: 'API Server'
)]
abstract class Controller
{
}
