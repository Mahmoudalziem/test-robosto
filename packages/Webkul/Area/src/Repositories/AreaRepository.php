<?php
namespace Webkul\Area\Repositories;

use Webkul\Core\Eloquent\Repository;

class AreaRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return 'Webkul\Area\Contracts\Area';
    }
}
