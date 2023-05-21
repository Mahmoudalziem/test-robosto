<?php

namespace Webkul\Customer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Customer\Http\Resources\Customer\AvatarAll;
use Webkul\Customer\Models\Avatar;

class AvatarController extends BackendBaseController
{

    /**
     * Load the avatars for the customer.
     *
     * @return JsonResponse
     */
    public function getAvatars()
    {
        $avatars = new AvatarAll(Avatar::get());
        // Fire Event
        Event::dispatch('get.avatars', $avatars);
        return $this->responseSuccess($avatars);
    }


    /**
     * Create New Avatar
     *
     * @return JsonResponse
     */
    public function create(Request $request)
    {
        $data = $request->only(['image', 'gender']);

        // First Upload image
        $imagePath = $this->uploadImages($data);
        DB::table('avatars')->insert([
            [
                'image'     =>  $imagePath,
                'gender'    =>  $data['gender']
            ]
        ]);
        // Fire Event
        Event::dispatch('avatar.create');
        return $this->responseSuccess();
    }


    /**
     * @param  int  $id
     * @return JsonResponse
     */
    public function delete($id)
    {
        $avatar = DB::table('avatars')->where('id', $id)->value('image');

        DB::table('avatars')->where('id', $id)->delete();

        Storage::delete($avatar);

        return $this->responseSuccess(null);
    }


    /**
     * @param array $data
     * @return void
     */
    public function uploadImages($data)
    {
        if (isset($data['image'])) {
            $request = request();

            $dir = 'avatars';

            if ($request->hasFile('image')) {

                // Save and Store new image
                $path = $request->file('image')->store($dir);

                return $path;
            }
        }
    }
}
