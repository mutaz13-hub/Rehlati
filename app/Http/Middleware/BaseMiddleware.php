<?php

namespace App\Http\Middleware;

use App\Traits\JsonResponseTrait;

abstract class BaseMiddleware
{
     use JsonResponseTrait;
}
