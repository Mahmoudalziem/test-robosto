<?php

namespace Webkul\Customer\Repositories;

use Webkul\Core\Eloquent\Repository;
use Illuminate\Support\Facades\Event;

class CustomerAddressRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return 'Webkul\Customer\Contracts\CustomerAddress';
    }

    /**
     * @param  array  $data
     * @return \Webkul\Customer\Contracts\CustomerAddress
     */
    public function create(array $data)
    {
        Event::dispatch('customer.addresses.create.before');

        $data['is_default'] = isset($data['is_default']) ? 1 : 0;
        
        $default_address = $this
            ->findWhere(['customer_id' => $data['customer_id'], 'is_default' => 1])
            ->first();

        if (isset($default_address->id) && $data['is_default']) {
            $default_address->update(['is_default' => 0]);
        }

        $address = $this->model->create($data);
        Event::dispatch('customer.addresses.create.after', $address);
        return $address;
    }

    /**
     * @param  array  $data
     * @param  int  $id
     * @return \Webkul\Customer\Contracts\CustomerAddress
     */
    public function update(array $data, $id)
    {
        $address = $this->find($id);
        Event::dispatch('customer.addresses.update.before', $id);

        $address->update($data);
        Event::dispatch('customer.addresses.update.after', $id);

        return $address;
    }
}