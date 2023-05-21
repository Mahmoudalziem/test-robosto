<?php

namespace Webkul\Admin\Http\Resources\Customer;

use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Admin\Http\Resources\Customer\CustomerWalletNotesAll;
use Webkul\Admin\Http\Resources\Customer\CustomerDevicesAll;

class CustomerSingle extends JsonResource {

    protected $append;

    public function __construct($resource, $append = null) {
        $this->append = $append;
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request) {
        return [
            'id' => $this->id,
            'email' => isset($this->email) ? $this->email : '-',
            'name' => isset($this->name) ? $this->name : '-',
            'phone' => isset($this->phone) ? $this->phone : '-',
            'landline' => (string) isset($this->landline) ? $this->landline : '-',
            'gender' => isset($this->gender) && $this->gender ? 1 : 0,
            'status' => (boolean) isset($this->status) ? $this->status : null,
            'is_online' => (boolean) $this->is_online,
            'otp_verified' => (boolean) isset($this->otp_verified) ? $this->otp_verified : null,
            'is_flagged' => (boolean) isset($this->is_flagged) ? $this->is_flagged : false,
            'source' => (string) isset($this->channel->name) ? $this->channel->name : '-',
            'area' => (string) isset($this->default_address) ? $this->default_address->area->name : '-',
            'wallet' => (double) isset($this->wallet) ? $this->wallet : 0,
            'newCustomer' => $this->append ? $this->append['newCustomer'] : null,
            'orders_count' => $this->orders ? $this->orders->count() : 0,
            'invitationsLogs_count' => $this->invitationsLogs ? $this->invitationsLogs->count() : 0,
            'invited_by' => $this->invitedBy ? ['id' => $this->invitedBy->id, 'name' => $this->invitedBy->name] : null,
            'customer_notes' => $this->customerNotes ? new CustomerNotesAll($this->customerNotes) : [],
            'wallet_notes' => $this->walletNotes ? new CustomerWalletNotesAll($this->walletNotes) : [],
            'customer_tags' => $this->tags ? new CustomerTagsAll($this->tags) : [],
            'customer_tags_id' => $this->tags ? $this->tags->pluck('id') : [],
            'avatar' => $this->avatar_url,
            'created_at' => isset($this->created_at) ? $this->created_at : null,
            'updated_at' => $this->updated_at,
        ];
    }

}
