<?php


namespace Webkul\Area\Http\Controllers\Api;

use http\Env\Request;
use Webkul\Area\Http\Resources\AreaAddressAll;
use Webkul\Area\Http\Resources\AreaAll;
use Webkul\Area\Repositories\AreaRepository;
use Webkul\Area\Http\Controllers\Api\Controller;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Customer\Repositories\CustomerAddressRepository;
use Webkul\API\Http\Resources\Customer\CustomerAddress as CustomerAddressResource;

class AreaController extends BackendBaseController
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


    public function __construct(AreaRepository $areaRepository)
    {

        $this->areaRepository = $areaRepository;
    }

    /**
     * Get user address.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function get()
    {
        return $this->responseSuccess($this->areaRepository->active()->allowedInApp()->get());

    }

    public function areaAddresses(CustomerAddressRepository $customerAddressRepository)
    {
        // if customer logged in
        // get area id from address table
        if(auth("customer")->check()){
            $customer= auth()->guard("customer")->user();
            // get default area from shipped address of the last order has been placed.

            // if not then get area id from address table
            $customerAddress= $customerAddressRepository->with('area')->findWhere(['customer_id'=>$customer->id]);
            if($customerAddress){
                $data=new AreaAddressAll($customerAddress);
            }else{
                $data=new AreaAll($this->areaRepository->active()->all());
            }

            return $this->responseSuccess($data);

        }else{ // get area from the default area table
            $data=new AreaAll($this->areaRepository->active()->all());
            return $this->responseSuccess($data);
        }
    }

    public function create(\Illuminate\Http\Request $request){
        return $this->areaRepository->create($request->all());
    }

}