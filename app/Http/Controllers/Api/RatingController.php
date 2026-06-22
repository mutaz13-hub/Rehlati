<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RatingResource;
use App\Http\Requests\RatingIndexRequest;
use App\Http\Requests\RatingStoreRequest;
use App\Http\Requests\RatingUpdateRequest;
use App\Http\Requests\RatingVoteRequest;
use App\Models\City;
use App\Models\Hotel;
use App\Models\Rating;
use App\Models\Region;
use App\Models\Room;
use App\Services\RatingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class RatingController extends Controller
{
    protected RatingService $service;

    public function __construct(RatingService $service)
    {
        $this->service = $service;
    }
    public function indexForHotel(RatingIndexRequest $request, Hotel $hotel)
    {
        $paginator = $this->service->indexByMorph(Hotel::MORPH_KEY, $hotel->id, $request->only('sort'));

        return $this->succeed(__('Ratings fetched'), RatingResource::collection($paginator));
    }

    public function indexForRoom(RatingIndexRequest $request, Room $room)
    {
        $paginator = $this->service->indexByMorph(Room::MORPH_KEY, $room->id, $request->only('sort'));

        return $this->succeed(__('Ratings fetched'), RatingResource::collection($paginator));
    }

    public function indexForCity(RatingIndexRequest $request, City $city)
    {
        $paginator = $this->service->indexByMorph(City::MORPH_KEY, $city->id, $request->only('sort'));

        return $this->succeed(__('Ratings fetched'), RatingResource::collection($paginator));
    }

    public function indexForRegion(RatingIndexRequest $request, Region $region)
    {
        $paginator = $this->service->indexByMorph(Region::MORPH_KEY, $region->id, $request->only('sort'));

        return $this->succeed(__('Ratings fetched'), RatingResource::collection($paginator));
    }

    public function store(RatingStoreRequest $request)
    {
        // $this->authorize('create', Rating::class);

        $data = $request->validated();

        $audio = $request->file('audio');
        $photo = $request->file('photo');

        $this->service->store($data, $audio, $photo);

        return $this->succeed(__('Rating created'), [], 201);
    }

    public function show(Rating $rating)
    {
        return $this->succeed(__('Rating fetched'), new RatingResource($rating->load('user')));
    }

    public function update(RatingUpdateRequest $request, Rating $rating)
    {
        $this->authorize('update', $rating);

        $data = $request->validated();
        $audio = $request->file('audio');
        $photo = $request->file('photo');

        $rating = $this->service->update($rating, $data, $audio, $photo);

        return $this->succeed(__('Rating updated'), new RatingResource($rating));
    }

    public function destroy(Rating $rating)
    {
        $this->authorize('delete', $rating);

        $this->service->destroy($rating);

        return $this->succeed(__('Rating deleted'));
    }

    public function vote(RatingVoteRequest $request, Rating $rating)
    {
        Gate::authorize('update', $rating);

        $data = $request->validated();

         $this->service->vote($rating, $data['vote']);

        return $this->succeed(__('Vote recorded'));
    }
}
