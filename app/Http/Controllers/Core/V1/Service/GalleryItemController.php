<?php

namespace App\Http\Controllers\Core\V1\Service;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Service\Logo\ShowRequest;
use App\Models\File;
use App\Models\Service;
use Illuminate\Http\Response;

class GalleryItemController extends Controller
{
    /**
     * GalleryItemController the specified resource.
     *
     * @param \App\Http\Requests\Service\Logo\ShowRequest $request
     * @param \App\Models\Service $service
     * @param \App\Models\File $file
     * @return \App\Models\File|\Illuminate\Http\Response
     */
    public function __invoke(ShowRequest $request, Service $service, File $file)
    {
        // Abort if the service does not have this file in it's gallery.
        abort_if(
            $service->serviceGalleryItems()
                ->where('file_id', '=', $file->id)
                ->doesntExist(),
            Response::HTTP_NOT_FOUND
        );

        event(EndpointHit::onRead($request, "Viewed gallery item for service [{$service->id}]", $service));

        // Return the file, or placeholder if the file is null.
        return $file->resizedVersion($request->max_dimension);
    }
}
