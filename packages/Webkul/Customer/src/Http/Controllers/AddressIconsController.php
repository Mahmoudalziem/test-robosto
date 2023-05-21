<?php

namespace Webkul\Customer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\Brand\Contracts\Brand;
use Webkul\Core\Http\Controllers\BackendBaseController;

class AddressIconsController extends BackendBaseController
{

    /**
     * Load the address_icons for the customer.
     *
     * @return JsonResponse
     */
    public function getIcons()
    {
        $icons = DB::table('address_icons')->get();

        // Fire Event
        Event::dispatch('get.address_icons', $icons);

        return $this->responseSuccess($icons);
    }


    /**
     * Create New Avatar
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request)
    {
        $data = $request->only(['image']);

        // First Upload image
        $imagePath = $this->uploadImages($data);

        DB::table('address_icons')->insert([
            [
                'image'     =>  $imagePath,
            ]
        ]);

        // Fire Event
        Event::dispatch('address_icon.create');

        return $this->responseSuccess();
    }


    /**
     * @param  int  $id
     * @return JsonResponse
     */
    public function delete($id)
    {
        $icon = DB::table('address_icons')->where('id', $id)->value('image');

        DB::table('address_icons')->where('id', $id)->delete();

        Storage::delete($icon);

        return $this->responseSuccess(null);
    }


    /**
     * @param array $data
     * @return false|string
     */
    public function uploadImages($data)
    {
        if (isset($data['image'])) {
            $request = request();

            $dir = 'icons';

            if ($request->hasFile('image')) {

                // Save and Store new image
                return $request->file('image')->store($dir);
            }
        }
    }
}
