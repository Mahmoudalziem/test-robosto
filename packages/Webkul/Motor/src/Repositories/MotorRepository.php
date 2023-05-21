<?php
namespace Webkul\Motor\Repositories;

use Webkul\Core\Eloquent\Repository;

class MotorRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return 'Webkul\Motor\Contracts\Motor';
    }
}
