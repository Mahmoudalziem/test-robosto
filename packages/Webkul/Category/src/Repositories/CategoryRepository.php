<?php

namespace Webkul\Category\Repositories;

use Webkul\Core\Eloquent\Repository;

class CategoryRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model()
    {
        return 'Webkul\Category\Contracts\Category';
    }

}