<?php
namespace Webkul\Driver\Http\Transformers;

use Illuminate\Support\Carbon;
use Webkul\Driver\Models\Driver;
use Flugg\Responder\Transformers\Transformer;

class DriverTransformer extends Transformer
{
    /**
     * List of available relations.
     *
     * @var string[]
     */
    protected $relations = [];

    /**
     * List of autoloaded default relations.
     *
     * @var array
     */
    protected $load = [];

    /**
     * Transform the model.
     *
     * @param  \App\Driver $driver
     * @return array
     */
    public function transform(Driver $driver)
    {
        $today = Carbon::now()->format('Y-m-d').'%';
        $driverMotorLoggedToday=$driver->motors()->wherePivot('created_at', 'like', $today)->count() >0 ? true:false;


        return [
            'id'            => $driver->id,
            'email'         => $driver->email,
            'firstName'    => $driver->first_name,
            'lastName'     => $driver->last_name,
            'image'         => $driver->image_url(),
            'liecese_image'         => $driver->imageIdUrl(),
            'name'          => $driver->name,
            'address'       => $driver->address,
            'phonePrivate'  => $driver->phone_private,
            'phoneWork'     => $driver->phone_work,
            'availability'  => $driver->availability,
            'isOnline'                => $driver->is_online?true:false,
            'driverMotorLoggedToday'                => $driverMotorLoggedToday,
            'status'                                => $driver->status,
            'created_at'        => $driver->created_at,
            'updated_at'    => $driver->updated_at,
        ];
    }
}
