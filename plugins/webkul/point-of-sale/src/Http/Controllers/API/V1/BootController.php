<?php

namespace Webkul\PointOfSale\Http\Controllers\API\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\PointOfSale\Models\Session;
use Webkul\PointOfSale\Services\BootLoader;

class BootController extends Controller
{
    public function __construct(
        protected BootLoader $boot,
    ) {}

    public function show(Session $session): JsonResponse
    {
        return response()->json([
            'data' => $this->boot->load($session->config, $session),
        ]);
    }
}
