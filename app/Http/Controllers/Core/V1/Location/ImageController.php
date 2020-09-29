<?php

namespace App\Http\Controllers\Core\V1\Location;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Location\Image\ShowRequest;
use App\Models\File;
use App\Models\Location;

class ImageController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param \App\Http\Requests\Location\Image\ShowRequest $request
     * @param \App\Models\Location $location
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @return \Illuminate\Http\Response
     */
    public function __invoke(ShowRequest $request, Location $location)
    {
        event(EndpointHit::onRead($request, "Viewed image for location [{$location->id}]", $location));

        // Get the image file associated.
        $file = $location->imageFile;

        // Return the file, or placeholder if the file is null.
        return optional($file)->resizedVersion($request->max_dimension)
            ?? Location::placeholderImage($request->max_dimension);
    }
}
