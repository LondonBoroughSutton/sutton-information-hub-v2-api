<?php

namespace App\Http\Controllers\Core\V1\Service;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Service\Logo\ShowRequest;
use App\Models\File;
use App\Models\Service;

class LogoController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param \App\Http\Requests\Service\Logo\ShowRequest $request
     * @param \App\Models\Service $service
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @return \Illuminate\Http\Response
     */
    public function __invoke(ShowRequest $request, Service $service)
    {
        event(EndpointHit::onRead($request, "Viewed logo for service [{$service->id}]", $service));

        // Get the logo file associated.
        $file = $service->logoFile;

        // Return the file, or placeholder if the file is null.
        return optional($file)->resizedVersion($request->max_dimension)
            ?? Service::placeholderLogo($request->max_dimension);
    }
}
