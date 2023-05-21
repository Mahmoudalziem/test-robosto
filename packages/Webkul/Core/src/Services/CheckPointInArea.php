<?php

namespace Webkul\Core\Services;

use App\Exceptions\AreaNotCoveredException;

class CheckPointInArea
{

    /**
     * @var array
     */
    private $location = [];

    /**
     * @param array $location
     */
    public function __construct(array $location)
    {
        $this->location = $location;
    }


    /**
     * @param array $point
     * @param array $polygon
     * 
     * @return bool
     */
    private function contains(array $point, array $polygon)
    {
        if ($polygon[0] != $polygon[count($polygon) - 1])
            $polygon[count($polygon)] = $polygon[0];
        $j = 0;
        $oddNodes = false;
        $x = $point[1];
        $y = $point[0];
        $n = count($polygon);
        for ($i = 0; $i < $n; $i++) {
            $j++;
            if ($j == $n) {
                $j = 0;
            }
            if ((($polygon[$i][0] < $y) && ($polygon[$j][0] >= $y)) || (($polygon[$j][0] < $y) && ($polygon[$i][0] >=
                $y))) {
                if ($polygon[$i][1] + ($y - $polygon[$i][0]) / ($polygon[$j][0] - $polygon[$i][0]) * ($polygon[$j][1] -
                    $polygon[$i][1]) < $x) {
                    $oddNodes = !$oddNodes;
                }
            }
        }
        return $oddNodes;
    }

    public function check()
    {
        $areas = config('areas.locations');

        foreach ($areas as $key => $area) {
            if ($this->contains([$this->location['lng'], $this->location['lat']], $area)) {
                return $key;
            }
        }
        return false;
    }
}
