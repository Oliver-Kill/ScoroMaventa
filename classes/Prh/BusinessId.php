<?php


namespace ScoroMaventa\Prh;


class BusinessId
{

    /**
     * Check that business id is formated correctly and checksum matches.
     *
     * @param string $businessId
     * @return boolean
     */
    public static function isValid(string $businessId)
    {

        if (PrhAPI::isValidBusinessId($businessId) == false) {
            return false;
        }

        // Remove all except numbers
        $businessId = preg_replace('/[^0-9]/', '', $businessId);

        // Some old business ids may have just 6 numbers + checksum
        $businessId = str_pad($businessId, 8, "0", STR_PAD_LEFT);

        // Calculate checksum
        $multipliers = [7, 9, 10, 5, 8, 4, 2];
        $checksum = 0;
        foreach (str_split($businessId) as $k => $v) {
            if (isset($multipliers[$k]) == false) break;

            $checksum += ((int)$v * $multipliers[$k]);
        }
        $checksum = $checksum % 11;

        if ($checksum == 1) return false;
        if ($checksum > 1) {
            $checksum = 11 - $checksum;
        }

        return (substr($businessId, -1) == $checksum);
    }

    public static function toOvt($businessId){
        return '0037' . stripNonNumbers($businessId);
    }
}