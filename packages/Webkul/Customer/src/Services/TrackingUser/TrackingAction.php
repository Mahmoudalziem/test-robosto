<?php
namespace Webkul\Customer\Services\TrackingUser;

use Webkul\Customer\Models\Customer;
use Webkul\Customer\Services\TrackingUser\Facebook\FacebookPixel;
use Webkul\Customer\Services\TrackingUser\TrackingType;

class TrackingAction
{
    /**
     * @var Customer
     */
    public $customer;

    /**
     * @var string
     */
    private $eventName;

    /**
     * @var array
     */
    private $data;


    public function __construct(string $eventName, Customer $customer, array $data = null)
    {
        $this->eventName = $eventName;
        $this->customer = $customer;
        $this->data = $data;
    }

    /**
     * Get Instance from Apply Type
     */
    public function getAllTypes()
    {
        return [
            new FacebookPixel($this->eventName, $this->customer, $this->data)
        ];
    }

    /**
     * Send The Action To All Providers
     * 
     * @return void
     */
    public function sendAction()
    {
        foreach ($this->getAllTypes() as $type) {
            $type->submitTheAction();
        }
    }
}