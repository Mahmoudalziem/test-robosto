<?php
namespace Webkul\Customer\Services\TrackingUser;

use Webkul\Customer\Models\Customer;

class Tracking
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
     * Apply Promotion on Items and Retun Items
     * 
     * @return void
     */
    public function send()
    {
        (new TrackingAction($this->eventName, $this->customer, $this->data))->sendAction();
    }
}