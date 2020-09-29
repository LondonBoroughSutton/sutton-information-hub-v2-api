<?php

namespace App\Http\Controllers\Core\V1\ServiceLocation;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceLocation\Image\ShowRequest;
use App\Models\File;
use App\Models\ServiceLocation;

class ImageController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param \App\Http\Requests\ServiceLocation\Image\ShowRequest $request
     * @param \App\Models\ServiceLocation $serviceLocation
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @return \Illuminate\Http\Response
     */
    public function __invoke(ShowRequest $request, ServiceLocation $serviceLocation)
    {
        event(EndpointHit::onRead($request, "Viewed image for service location [{$serviceLocation->id}]", $serviceLocation));

        // Get the image file associated.
        $file = $serviceLocation->imageFile;

        // Return the file, or placeholder if the file is null.
        return optional($file)->resizedVersion($request->max_dimension)
            ?? ServiceLocation::placeholderImage($request->max_dimension);
    }
}
