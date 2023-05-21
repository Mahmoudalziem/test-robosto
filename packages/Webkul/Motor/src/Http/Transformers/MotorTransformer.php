<?php
namespace Webkul\Motor\Http\Transformers;

use Webkul\Motor\Models\Motor;
use Flugg\Responder\Transformers\Transformer;

class MotorTransformer extends Transformer
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
     * @param  \App\Motor $driver
     * @return array
     */
    public function transform(Motor $driver)
    {
        return [
            'id'            => $driver->id,
            'email'         => $driver->email,
            'firstName'    => $driver->first_name,
            'lastName'     => $driver->last_name,
            'profilePictureURL'         => $driver->image,
            'name'          => $driver->name,
            'address'       => $driver->address,
            'phonePrivate' => $driver->phone_private,
            'phoneWork'    => $driver->phone_work,
            'status'        => $driver->status,
            'created_at'    => $driver->created_at,
            'updated_at'    => $driver->updated_at,
        ];
    }
}
