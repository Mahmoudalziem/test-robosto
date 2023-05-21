<?php
namespace Webkul\Core\Services;


use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Measure
{
    /*::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::*/
    /*::                                                                         :*/
    /*::  This routine calculates the distance between two points (given the     :*/
    /*::  latitude/longitude of those points). It is being used to calculate     :*/
    /*::  the distance between two locations using GeoDataSource(TM) Products    :*/
    /*::                                                                         :*/
    /*::  Definitions:                                                           :*/
    /*::    South latitudes are negative, east longitudes are positive           :*/
    /*::                                                                         :*/
    /*::  Passed to function:                                                    :*/
    /*::    lat1, lon1 = Latitude and Longitude of point 1 (in decimal degrees)  :*/
    /*::    lat2, lon2 = Latitude and Longitude of point 2 (in decimal degrees)  :*/
    /*::    unit = the unit you desire for results                               :*/
    /*::           where: 'M' is statute miles (default)                         :*/
    /*::                  'K' is kilometers                                      :*/
    /*::                  'N' is nautical miles                                  :*/
    /*::  Worldwide cities and other features databases with latitude longitude  :*/
    /*::  are available at https://www.geodatasource.com                          :*/
    /*::                                                                         :*/
    /*::  For enquiries, please contact sales@geodatasource.com                  :*/
    /*::                                                                         :*/
    /*::  Official Web site: https://www.geodatasource.com                        :*/
    /*::                                                                         :*/
    /*::         GeoDataSource.com (C) All Rights Reserved 2018                  :*/
    /*::                                                                         :*/
    /*::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::*/

    /*
     * usage ==================================================================
    echo distance(32.9697, -96.80322, 29.46786, -98.53506, "M") . " Miles<br>";
    echo distance(32.9697, -96.80322, 29.46786, -98.53506, "K") . " Kilometers<br>";
    echo distance(32.9697, -96.80322, 29.46786, -98.53506, "N") . " Nautical Miles<br>";
    ===========================================================================
     */
    public static function distanceOne($lat1, $lon1, $lat2, $lon2, $unit, $data) {
        if (($lat1 == $lat2) && ($lon1 == $lon2)) {
            Log::info("Latitude equal Longitude");
            return ['distance' => 0, 'data' => $data];
        }
        else {
            $theta = $lon1 - $lon2;
            $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
            $dist = acos($dist);
            $dist = rad2deg($dist);
            $miles = $dist * 60 * 1.1515;
            $unit = strtoupper($unit);

            if ($unit == "K") {
                return [ 'distance' => ($miles * 1.609344), 'data' => $data];
            } else if ($unit == "N") {
                return ['distance' => ($miles * 0.8684), 'data' => $data];
            } else {
                return ['distance' => $miles, 'data' => $data];
            }
        }
    }

    /*
        $pointsArray=[
            [51.159313, 22.366813],
            [51.153109, 22.387458],
            [51.167133, 22.39142],
        ];
      */
    public static function distanceMany($lat1, $lon1 ,array $pointsArray ,$unit = 'K'){

        $measures=[];
        foreach ($pointsArray as  $val){ // [ $row[0] ==> $lat2, $row[1] ==>$lan2 ]
          array_push($measures,self::distanceOne($lat1, $lon1,$val[0] ,$val[1] ,$unit, $val[2]));
        }
        return $measures;
    }

    public static function abstractDistance($lat1, $lon1, $lat2, $lon2, $unit = 'K')
    {
        if (($lat1 == $lat2) && ($lon1 == $lon2)) {
            return 0;
        } else {
            $theta = $lon1 - $lon2;
            $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
            $dist = acos($dist);
            $dist = rad2deg($dist);
            $miles = $dist * 60 * 1.1515;
            $unit = strtoupper($unit);

            if ($unit == "K") {
                return ($miles * 1.609344);
            } else if ($unit == "N") {
                return ($miles * 0.8684);
            } else {
                return $miles;
            }
        }
    }


    public static function getMaxDistance( array $distances  ){
        return max($distances);
    }

    public static function getMinDistance( array $distances  ){
        return min($distances);
    }


}
