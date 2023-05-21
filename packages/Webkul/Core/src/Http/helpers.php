<?php

use Webkul\Core\Core;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Customer\Models\Customer;

if (!function_exists('core')) {
    function core()
    {
        return app()->make(Core::class);
    }
}

if (!function_exists('array_permutation')) {
    function array_permutation($input)
    {
        $results = [];

        foreach ($input as $key => $values) {
            if (empty($values)) {
                continue;
            }

            if (empty($results)) {
                foreach ($values as $value) {
                    $results[] = [$key => $value];
                }
            } else {
                $append = [];

                foreach ($results as &$result) {
                    $result[$key] = array_shift($values);

                    $copy = $result;

                    foreach ($values as $item) {
                        $copy[$key] = $item;
                        $append[] = $copy;
                    }

                    array_unshift($values, $result[$key]);
                }

                $results = array_merge($results, $append);
            }
        }

        return $results;
    }
}

if (!function_exists('requestWithCurl')) {
    function requestWithCurl($url, $type = 'GET', $data = null, $headers = [], $decodeResponse = true)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, $type == 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $result = curl_exec($ch);
        curl_close($ch);
        
        if (!$decodeResponse) {
            return $result;
        }

        return json_decode($result, true);
    }
}


if (!function_exists('replaceHashtagInText')) {

    /**
     * @param string $text
     * @param Customer $customer
     *
     * @return string
     */
    function replaceHashtagInText(string $text, Customer $customer)
    {
        $hashTags = config('robosto.HASHTAGS');
        foreach ($hashTags as $hashTag) {
            $hashTag = '#' . $hashTag;
            if ($hashTag == '#name' && strpos($text, $hashTag) !== false) {
                $name = explode(' ', $customer->name)[0];
                $text = str_replace($hashTag, $name, $text);
            } elseif ($hashTag == '#email' && strpos($text, $hashTag) !== false) {
                $text = str_replace($hashTag, $customer->email ?? $customer->name, $text);
            } elseif ($hashTag == '#phone' && strpos($text, $hashTag) !== false) {
                $text = str_replace($hashTag, $customer->phone, $text);
            }
        }
        return $text;
    }
}


if (!function_exists('saveNotifiers')) {

    /**
     * @param array $customers
     * @param string $entityType
     * @param int $entityId
     *
     * @return bool
     */
    function saveNotifiers(array $customers, string $entityType, int $entityId)
    {
        $customerCollection = collect($customers);
        foreach ($customerCollection->chunk(10) as $chunkedCustomerList){
            Log::info($chunkedCustomerList);
            $data = [];
            foreach ($chunkedCustomerList as $customer) {
                $data[] = [
                    'entity_type'   => $entityType,
                    'entity_id' => $entityId,
                    'customer_id'   => $customer,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];
            }
            DB::table('notifiers')->insert($data);
            $data = [];
        }
        // $data = [];
        // foreach ($customers as $customer) {
        //     $data[] = [
        //         'entity_type'   => $entityType,
        //         'entity_id' => $entityId,
        //         'customer_id'   => $customer,
        //         'created_at'    => now(),
        //         'updated_at'    => now(),
        //     ];
        //     Log::info(count($data));
        //     if(count($data)==50){
        //         DB::table('notifiers')->insert($data);
        //         $data = [];
        //     }
        // }
        // DB::table('notifiers')->insert($data);
    }
}
