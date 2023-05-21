<?php

namespace Webkul\Customer\Transformers;



use Webkul\Core\Models\Address as CustomerAddress;
use Flugg\Responder\Transformers\Transformer;
class CustomerAddressTransfromer extends Transformer
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
     * @param  \Webkul\Customer\Transformers $customerAddressTransfromer
     * @return array
     */
    public function transform(CustomerAddress $customerAddress)
    {
        return [
            'id' => (int) $customerAddress->id,
            'address1' => (int) $customerAddress->address1,
        ];
    }
}
