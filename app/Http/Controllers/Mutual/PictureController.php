<?php

namespace App\Http\Controllers\Mutual;

use App\Http\Controllers\Controller;
use App\Http\Resources\PictureResource;
use App\Models\City;
use App\Models\Hotel;
use App\Models\Region;
use App\Services\PictureService;

class PictureController extends Controller
{
    public function __construct(public PictureService $pic_service){}
    
    public function city(City $city)
    {
        $pictures = $this->pic_service->city($city);

        $resource = PictureResource::collection($pictures)->response()->getData(true);

        return $this->succeed(__('Pictures retrieved successfully'),[ 
           'pics' => $resource['data'],
           'meta' => $resource['meta']
        ]);
    }

    public function region(Region $region)
    {
        $pictures = $this->pic_service->region($region);
        $resource = PictureResource::collection($pictures)->response()->getData(true);

        return $this->succeed(__('Pictures retrieved successfully'),[
            'pics' => $resource['data'],
            'meta' => $resource['meta']
        ]);
    }

    public function hotel(Hotel $hotel)
    {
        $pictures = $this->pic_service->hotel($hotel);
        $resource = PictureResource::collection($pictures)->response()->getData(true);

        return $this->succeed(__('Pictures retrieved successfully'),[
            'pics' => $resource['data'],
            'meta' => $resource['meta']
        ]);
    }
}
