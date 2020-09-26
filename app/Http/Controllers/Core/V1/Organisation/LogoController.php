<?php

namespace App\Http\Controllers\Core\V1\Organisation;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organisation\Logo\ShowRequest;
use App\Models\File;
use App\Models\Organisation;

class LogoController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param \App\Http\Requests\Organisation\Logo\ShowRequest $request
     * @param \App\Models\Organisation $organisation
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @return \Illuminate\Http\Response
     */
    public function __invoke(ShowRequest $request, Organisation $organisation)
    {
        event(EndpointHit::onRead($request, "Viewed logo for organisation [{$organisation->id}]", $organisation));

        // Get the logo file associated.
        $file = $organisation->logoFile;

        // Return the file, or placeholder if the file is null.
        return optional($file)->resizedVersion($request->max_dimension)
            ?? Organisation::placeholderLogo($request->max_dimension);
    }
}
