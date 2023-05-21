<?php

namespace Webkul\Admin\Repositories\Customer;

use Webkul\Core\Eloquent\Repository;
use Webkul\Customer\Contracts\CustomerAddress  as CustomerAddressContract;
class AdminCustomerAddressRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return CustomerAddressContract::class;
    }
}