<?php

namespace App\Http\Controllers\Core\V1;

use App\Http\Controllers\Controller;
use App\Repositories\NhsConditions\NhsConditionsRepository;

class NhsConditionController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param \App\Repositories\NhsConditions\NhsConditionsRepository $repository
     * @param string $slug
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function show(NhsConditionsRepository $repository, string $slug)
    {
        return $repository->find($slug);
    }
}
