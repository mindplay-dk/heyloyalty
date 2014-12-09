<?php

namespace mindplay\heyloyalty;

use RuntimeException;

/**
 * This class is responsible for mediating values between Hey Loyalty Field values and native PHP values.
 *
 * TODO integrate this
 */
class HeyLoyaltyMediator
{
    /**
     * @param string $format the field format; one of the HeyLoyaltyField::FORMAT_* constants
     * @param mixed $value Hey Loyalty field value
     * 
     * @return mixed native PHP value
     */
    public function parseValue($format, $value)
    {
        switch ($format) {
            case HeyLoyaltyField::FORMAT_BOOLEAN:
                return $value ? true : false;

            case HeyLoyaltyField::FORMAT_TEXT:
                return utf8_decode($value);

            case HeyLoyaltyField::FORMAT_DATE:
                if ($value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
                    return null; // not a valid date
                }
                return strtotime($value);

            case HeyLoyaltyField::FORMAT_NUMBER:
                return intval($value);

            case HeyLoyaltyField::FORMAT_PASSWORD:
                return null; // skip password field

            case HeyLoyaltyField::FORMAT_CHOICE:
                if (isset($value['id'])) {
                    return $value['id'];
                }
                return null;

            case HeyLoyaltyField::FORMAT_MULTI:
                $options = array();
                foreach ($value as $option) {
                    $options[] = $option['id'];
                }
                return $options;

            default:
                throw new RuntimeException("unsupported field format: {$format}");
        }
    }
}
