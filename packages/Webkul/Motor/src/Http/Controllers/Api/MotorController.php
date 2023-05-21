<?php


namespace Webkul\Motor\Http\Controllers\Api;

use Illuminate\Support\Facades\Event;
use Webkul\Motor\Http\Requests\CreateMotorRequest;

use Webkul\Motor\Repositories\MotorRepository;


class MotorController extends Controller
{
    /**
     * Contains current guard
     *
     * @var array
     */
    protected $guard;

    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    /**
     * CustomerAddressRepository object
     *
     * @var \Webkul\Customer\Repositories\CustomerAddressRepository
     */
    protected $areaRepository;
    /**
     * @var MotorRepository
     */
    private $motorRepository;

    protected $message;

    public function __construct(MotorRepository $motorRepository)
    {
        $this->motorRepository = $motorRepository;
    }


    // admin can create motor profile
    public function create(CreateMotorRequest $request)
    {
        $data = $request->all();
        Event::dispatch('motor.create.before');
        $motor = $this->motorRepository->create($data);
        Event::dispatch('motor.create.after', $motor);
        return responder()->success($motor);
    }

    // get all
    public function get()
    {
        $motor = $this->motorRepository->all();
        return responder()->success($motor);
    }

    // get by id
    public function getById($id)
    {
        $motor = $this->motorRepository->find($id);
        return responder()->success($motor);
    }
}