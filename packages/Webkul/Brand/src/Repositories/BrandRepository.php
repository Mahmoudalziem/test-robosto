<?php

namespace Webkul\Brand\Repositories;

use Webkul\Core\Eloquent\Repository;

class BrandRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return 'Webkul/Brand/Contracts/Brand';
    }
}