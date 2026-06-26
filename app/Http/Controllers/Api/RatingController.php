<?php

namespace App\Http\Controllers\Api;

use App\Enums\VoteType;
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
        $result = $this->service->indexByMorph(Hotel::MORPH_KEY, $hotel->id, $request->only(['sort']));

        return $this->succeed(__('Ratings fetched'),[ 
           'ratings' => RatingResource::collection($result['ratings']),
           'meta' => [
            'next_cursor' => $result['ratings']->nextCursor(),
            'prev_cursor' => $result['ratings']->previousCursor(),
            'total' => $result['total']
           ]
        ]);
    }

    public function indexForRoom(RatingIndexRequest $request, Room $room)
    {
        $result = $this->service->indexByMorph(Room::MORPH_KEY, $room->id, $request->only(['sort', 'per_page']));

        return $this->succeed(__('Ratings fetched'),[ 
           'ratings' => RatingResource::collection($result['ratings']),
           'meta' => [
            'next_cursor' => $result['ratings']->nextCursor(),
            'prev_cursor' => $result['ratings']->previousCursor(),
            'total' => $result['total']
           ]
        ]);
    }

    public function indexForCity(RatingIndexRequest $request, City $city)
    {
        $result = $this->service->indexByMorph(City::MORPH_KEY, $city->id, $request->only(['sort']));

       $ratings = RatingResource::collection($result['ratings'])->response()->getData(true);

        $ratings['links']['total'] = $result['total'];

        return $this->succeed(__('Ratings fetched'),[ 
           'ratings' => $ratings['data'],
           'meta' => $ratings['links']
        ]);
    }

    public function indexForRegion(RatingIndexRequest $request, Region $region)
    {
        $result = $this->service->indexByMorph(Region::MORPH_KEY, $region->id, $request->only(['sort', 'per_page']));

        return $this->succeed(__('Ratings fetched'),[ 
           'ratings' => RatingResource::collection($result['ratings']),
           'meta' => [
            'next_cursor' => $result['ratings']->nextCursor(),
            'prev_cursor' => $result['ratings']->previousCursor(),
            'total' => $result['total']
           ]
        ]);
    }

    public function store(RatingStoreRequest $request)
    {
        $this->service->store(
            $request->validated(),
            $request->route()->getName(),
            (int) $request->id,
            $request->file('audio'),
            $request->file('photo')
        );

        return $this->succeed(__('Rating created'), [], 201);
    }

    public function show(Rating $rating)
    {
        return $this->succeed(__('Rating fetched'), new RatingResource($rating->load('user')));
    }

    public function update(RatingUpdateRequest $request, Rating $rating)
    {
        Gate::authorize('update', $rating);

        $data = $request->validated();
        $audio = $request->file('audio');
        $photo = $request->file('photo');

       $this->service->update($rating, $data, $audio, $photo);

        return $this->succeed(__('Rating updated'));
    }

    public function destroy(Rating $rating)
    {
        Gate::authorize('delete', $rating);

        $this->service->destroy($rating);

        return $this->succeed(__('Rating deleted'));
    }

    public function vote(RatingVoteRequest $request, Rating $rating)
    {
        Gate::authorize('vote', $rating);

        $data = $request->validated();

         $this->service->vote($rating, VoteType::from($data['vote']));

        return $this->succeed(__('Vote recorded'));
    }
}
