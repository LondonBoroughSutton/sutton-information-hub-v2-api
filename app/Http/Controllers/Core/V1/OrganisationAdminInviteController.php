<?php

namespace App\Http\Controllers\Core\V1;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrganisationAdminInvite\ShowRequest;
use App\Http\Requests\OrganisationAdminInvite\StoreRequest;
use App\Http\Resources\OrganisationAdminInviteResource;
use App\Models\Organisation;
use App\Models\OrganisationAdminInvite;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class OrganisationAdminInviteController extends Controller
{
    /**
     * OrganisationAdminInviteController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api')->except('show');
    }

    /**
     * @param \App\Http\Requests\OrganisationAdminInvite\StoreRequest $request
     * @return mixed
     */
    public function store(StoreRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $organisations = Organisation::query()
                ->whereIn('id', $request->input('organisations.*.organisation_id'))
                ->get();

            // Retrieve corresponding organisations, and filter out those using email, but without an email associated.
            $organisations = $organisations
                ->map(
                    function (Organisation $organisation) use ($request): Organisation {
                        $input = Arr::first(
                            $request->input('organisations'),
                            function (array $item) use ($organisation): bool {
                                return $item['organisation_id'] === $organisation->id;
                            }
                        );

                        $organisation->use_email = $input['use_email'];

                        return $organisation;
                    }
                )
                ->filter(
                    function (Organisation $organisation) use ($request): bool {
                        if ($organisation->use_email) {
                            return $organisation->email !== null;
                        }

                        return true;
                    }
                );

            $organisationAdminInvites = $organisations->map(
                function (Organisation $organisation): OrganisationAdminInvite {
                    return $organisation->organisationAdminInvites()->create([
                        'email' => $organisation->use_email ? $organisation->email : null,
                    ]);
                }
            );

            foreach ($organisationAdminInvites as $organisationAdminInvite) {
                event(EndpointHit::onCreate(
                    $request,
                    "Created organisation admin invite [{$organisationAdminInvite->id}]",
                    $organisationAdminInvite
                ));
            }

            return OrganisationAdminInviteResource::collection($organisationAdminInvites)
                ->response($request)
                ->setStatusCode(Response::HTTP_CREATED);
        });
    }

    /**
     * @param \App\Http\Requests\OrganisationAdminInvite\ShowRequest $request
     * @param \App\Models\OrganisationAdminInvite $organisationAdminInvite
     * @return \App\Http\Resources\OrganisationAdminInviteResource
     */
    public function show(ShowRequest $request, OrganisationAdminInvite $organisationAdminInvite)
    {
        $baseQuery = OrganisationAdminInvite::query()
            ->where('id', $organisationAdminInvite->id);

        $organisationAdminInvite = QueryBuilder::for($baseQuery)
            ->firstOrFail();

        event(EndpointHit::onRead(
            $request,
            "Viewed organisation admin invite [{$organisationAdminInvite->id}]",
            $organisationAdminInvite
        ));

        return new OrganisationAdminInviteResource($organisationAdminInvite);
    }
}
