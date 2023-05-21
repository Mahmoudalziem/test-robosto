<?php
namespace Webkul\Core\Services\LocationService\Distance;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class DistanceService extends DistanceAPI
{

    private $locations;

    public function __construct()
    {
    }

    /**
     * Get Nearest Drivers to Customer and Sort them based on time
     *
     * @return array
     */
    public function getNearesDriversToCustomer($locations)
    {
        return $this->getLocationsDistances($locations);
    }

    /**
     * Loop Through Locations
     *
     * @param array $locations
     * @return array
     */
    private function getLocationsDistances(array $locations)
    {
        $sumOfDistanceInTime = [];
        foreach ($locations as $location) {
            // Call API
            $reponse = $this->calculateDistance($location);

            // Handle Top-Level Response
            // If Response has an Error, then escape from Service
            if (!$this->handleTopLevelResponse($reponse)) {
                break;
            }

            // Handle Element-Level Response
            // If Response has an Error, then escape this Driver from Queue
            if (!$this->handleElementLevelResponse($reponse)) {
                continue;
            }

            // Get Duration in time between driver and customer
            $distanceInTime = $this->getDurationInMinutes($reponse);

            // Prepare Data
            $sumOfDistanceInTime[] = [
                'driver_id'     =>  $location['driver_id'] ?? null,
                'warehouse_id'  =>  $location['warehouse_id'] ?? null,
                'time'          =>  $distanceInTime
            ];
        }

        // Sort the distances between each Driver and Customer based on time
        $result = $this->sortLocations($sumOfDistanceInTime);
        Log::info($result);

        // Return Distances Sorted
        return $result;
    }

    /**
     * Loop Through Locations
     *
     * @param array $locations
     * @return array|bool
     */
    public function DistancesBetweenOriginsAndDestinations(array $location)
    {
        $sumOfDistanceInTime = [];

        
        // Call API
        $reponse = $this->calculateDistance($location);


        // Handle Top-Level Response
        // If Response has an Error, then escape from Service
        if (!$this->handleTopLevelResponse($reponse)) {
            return false;
        }

        return $reponse;

    }

    /**
     * Get Nearest Drivers to Customer and Sort them based on time
     *
     * @return array
     */
    public function getDistanceBetweenDriverToCustomer($locations)
    {
        return $this->getLocationsDriverToCustomerDistance( $locations);
    }

    /**
     * Loop Through Locations
     *
     * @param array $locations
     * @return array
     */
    private function getLocationsDriverToCustomerDistance(array $locations)
    {
        $sumOfDistanceInTime = [];
        foreach ($locations as $location) {
            // Call API
           // return $location;
            $reponse = $this->calculateDistance($location);

            // Handle Top-Level Response
            // If Response has an Error, then escape from Service
            if (!$this->handleTopLevelResponse($reponse)) {
                break;
            }

            // Handle Element-Level Response
            // If Response has an Error, then escape this Driver from Queue
            if (!$this->handleElementLevelResponse($reponse)) {
                continue;
            }

            // Get Duration in time between driver and customer
            $distanceInTime = $this->convertSecondsToMinutes($reponse['rows'][0]['elements'][0]['duration']['value']);

            // Prepare Data
            $sumOfDistanceInTime[] = [
                'driver_id'     =>  $location['driver_id'],
                'customer_id'   =>  $location['customer_id'],
                'time'          =>  $distanceInTime
            ];
        }
        // Sort the distances between each Driver and Customer based on time
        $array_column = array_column($sumOfDistanceInTime, 'time');
        array_multisort($array_column, SORT_ASC, $sumOfDistanceInTime);
        // Return Distances Sorted
        return $sumOfDistanceInTime;
    }
    /**
     * Calculate Distance for the givenLocation
     *
     * @param array $location
     * @return array
     */
    private function calculateDistance(array $location)
    {
        // Prepare URL
        $url = $this->prepareDistanceMatrixApiURL($location);
        Log::info($url);
        
        // Call API
        return $this->callUrlUsingCurl($url);
        
        // $response = Http::get($url);
        // return $response->json();
    }

    private function callUrlUsingCurl(string $url)
    {
        Log::info("Using Wrapper Curl");

        return requestWithCurl($url);
    }

    /**
     * Handle API Repsonse
     *
     * @param array $response
     * @return bool
     */
    private function handleTopLevelResponse(array $response)
    {
        // Handle Top-Level Request
        $invalidResponses = ['INVALID_REQUEST', 'MAX_ELEMENTS_EXCEEDED', 'OVER_DAILY_LIMIT', 'OVER_QUERY_LIMIT', 'REQUEST_DENIED', 'UNKNOWN_ERROR'];

        if (in_array($response['status'], $invalidResponses)) {

            Event::dispatch('google-distance.response-error', $response['status']);

            return false;
        }

        return true;
    }

    /**
     * Handle Element Level API Repsonse
     *
     * @param array $response
     * @return bool
     */
    private function handleElementLevelResponse(array $response)
    {
        $status = $response['rows'][0]['elements'][0]['status'];

        // Handle Top-Level Request
        $invalidResponses = ['NOT_FOUND', 'ZERO_RESULTS', 'MAX_ROUTE_LENGTH_EXCEEDED'];

        if (in_array($status, $invalidResponses)) {

            Event::dispatch('google-distance.response-error.for-driver', $response['status']);

            return false;
        }

        return true;
    }
    

    /**
     * Get Duration in minutes from Response and SUM them
     *
     * @param array $response
     * @return float|int
     */
    private function getDurationInMinutes(array $response)
    {
        $distanceInTime = [];
        // First, Get Distance between Warehouse and Driver In Minutes
        if (isset($response['rows'][0]['elements'][0]['duration'])) {
            $distanceInTime['from_warehouse_to_driver']   = $this->convertSecondsToMinutes( $response['rows'][0]['elements'][0]['duration']['value']);
        }

        // First, Get Distance between Warehouse and Customer In Minutes
        if (isset($response['rows'][0]['elements'][1]['duration'])) {
            $distanceInTime['from_warehouse_to_customer'] = $this->convertSecondsToMinutes( $response['rows'][0]['elements'][1]['duration']['value']);
        }

        // Return distance from warehouse to driver (+) distance from warehouse to customer in Minutes
        return array_sum($distanceInTime);
    }

    /**
     * @param array $results
     * 
     * @return array
     */
    private function sortLocations(array $results)
    {
        // Sort the distances between each Driver and Customer based on time
        $array_column = array_column($results, 'time');
        array_multisort($array_column, SORT_ASC, $results);

        return $results;
    }

    /**
     * Convert Seconds to Minutes
     *
     * @param int $seconds
     * @return int $minutes
     */
    private function convertSecondsToMinutes(int $seconds)
    {
        return (int) ceil(($seconds / 60));
    }
}
