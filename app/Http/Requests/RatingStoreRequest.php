<?php

namespace App\Http\Requests;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\CarAgency;
use App\Models\City;
use App\Models\Hotel;
use App\Models\Region;
use App\Models\Room;
use App\Models\TouristGuide;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class RatingStoreRequest extends ApiFormRequest
{
    public function authorize(): bool|Response
    {
        $route_name = $this->route()->getName();
        $alias = null;
        if ($route_name === 'ratings.cities') {
            $alias = City::MORPH_KEY;
        } elseif ($route_name === 'ratings.hotels') {
            $alias = Hotel::MORPH_KEY;
        } elseif ($route_name === 'ratings.rooms') {
            $alias = Room::MORPH_KEY;
        } elseif ($route_name === 'ratings.regions') {
            $alias = Region::MORPH_KEY;
        } elseif ($route_name === 'ratings.car_agencies') {
            $alias = CarAgency::MORPH_KEY;
        } elseif ($route_name === 'ratings.tourist_guides') {
            $alias = TouristGuide::MORPH_KEY;
        }

        if ($alias === null) {
            return false;
        }

        $model = Relation::getMorphedModel($alias);

        Gate::authorize('rate', [$model, $model::findOrFail((int) $this->id)]);

        return true;
    }

    public function rules()
    {
        return [
            'rate' => ['required', 'numeric', 'integer', 'min:1', 'max:5'],
            'type' => ['required', 'string', 'in:text,audio'],
            'body' => [Rule::requiredIf($this->type === 'text'), Rule::prohibitedIf($this->type === 'audio'), 'nullable', 'string', 'max:255'],
            'audio' => [Rule::prohibitedIf($this->type === 'text'), Rule::requiredIf($this->type === 'audio'), 'nullable', 'file', 'mimes:mp3,wav,aac,m4a,ogg', 'max:10240'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
