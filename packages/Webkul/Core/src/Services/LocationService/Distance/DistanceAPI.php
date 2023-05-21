<?php

namespace Webkul\Core\Services\LocationService\Distance;

class DistanceAPI
{
    protected $outputFormat = 'json';
    protected $units = 'metric';
    protected $mode = 'driving';
    protected $language = 'en';
    protected $region = 'eg';
    protected $traffic_model = 'best_guess';
    protected $base_url = 'https://maps.googleapis.com/maps/api/distancematrix/';

    /**
     * Prepare Origins Parameter
     *
     * @param array $location
     * @return string
     */
    protected function originsParameter(array $location)
    {
        $source = '';
        $locationsSourceCount = count($location['origins']);
        $pipe = '';
        
        for ($i = 0; $i < $locationsSourceCount; $i++) {
            $pipe = ($locationsSourceCount-1 == $i) ? '' : '|';
            $source .= "{$location['origins'][$i ]['lat']},{$location['origins'][$i ]['long']}" . $pipe;
        }
        return "origins={$source}";
    }

    /**
     * Prepare Destinations Parameter
     *
     * @param $location
     * @return string
     */
    protected function destinationsParameter($location)
    {
        $distance = '';
        $locationsDistanceCount = count($location['dsetinations']);
        $pipe = '';

        for ($i = 0; $i < $locationsDistanceCount; $i++) {
            $pipe = ($locationsDistanceCount-1 == $i) ? '' : '|';
            $distance .= "{$location['dsetinations'][$i]['lat']},{$location['dsetinations'][$i]['long']}" . $pipe;
        }
        return "destinations={$distance}";
    }

    /**
     * Prepare Requets Optional Parameters
     *
     * @return string
     */
    protected function optionalParameters()
    {
        return "mode={$this->mode}&language={$this->language}&units={$this->units}&region={$this->region}&key={$this->getApiKey()}";
    }

    /**
     * Get API_KEY from Config
     *
     * @return string
     */
    private function getApiKey()
    {
        return config('robosto.GOOGLE_API_KEY');
    }

    /**
     * Google distance matrix api URL
     *
     * @param array $location
     * @return string
     */
    protected function prepareDistanceMatrixApiURL(array $location)
    {
        return $this->base_url . $this->outputFormat . '?' . $this->originsParameter($location) . '&' . $this->destinationsParameter($location) . '&' . $this->optionalParameters();
    }

}