<?php

namespace Webkul\Customer\Transformers;

use Webkul\Customer\Models\Customer;
use Flugg\Responder\Transformers\Transformer;

class CustomerTransformer extends Transformer
{
    /**
     * List of available relations.
     *
     * @var string[]
     */
    protected $relations = [ ];

    /**
     * List of autoloaded default relations.
     *
     * @var array
     */
    protected $load = [];

    public function transform(Customer $customer)
    {

        return [
            'id' => (int) $customer->id,
            'source' => (string) isset($customer->channel->name)?$customer->channel->name:'-' ,
            'name' => (string) $customer->name,
            'email' => (string) $customer->email,
            'phone' => (string) $customer->phone,
            'landline' => (string) $customer->landline,
            'gender' => (string) $customer->gender?'Female':'Male',
            'status' => (boolean) $customer->status,
            'otp_verified' => (boolean) $customer->otp_verified,
            'is_flagged' => (boolean) $customer->is_flagged,
            'wallet' => (double) $customer->wallet,
            'created_at' =>  $customer->created_at,
            'updated_at' =>  $customer->updated_at,
        ];
    }
}
