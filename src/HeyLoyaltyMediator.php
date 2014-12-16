<?php

namespace mindplay\heyloyalty;

use DateTime;
use DateTimeZone;
use RuntimeException;

/**
 * This class is responsible for mediating between Hey Loyalty Field values and native PHP values.
 */
class HeyLoyaltyMediator
{
    /**
     * Parse a field value obtained via the Hey Loyalty API and return a native PHP value.
     *
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
                return $this->parseDateTime($value);

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

    /**
     * Format a native PHP value as a field value for use with the Hey Loyalty API.
     *
     * @param string $format the field format; one of the HeyLoyaltyField::FORMAT_* constants
     * @param mixed $value native PHP value
     *
     * @return mixed Hey Loyalty field value
     */
    public function formatValue($format, $value)
    {
        switch ($format) {
            case HeyLoyaltyField::FORMAT_BOOLEAN:
                return $value ? '1' : '0';

            case HeyLoyaltyField::FORMAT_TEXT:
            case HeyLoyaltyField::FORMAT_PASSWORD:
                return utf8_encode((string)$value);

            case HeyLoyaltyField::FORMAT_DATE:
                return $this->formatDateTime($value);

            case HeyLoyaltyField::FORMAT_NUMBER:
                return (string)$value;

            case HeyLoyaltyField::FORMAT_CHOICE:
                return (string)$value;

            case HeyLoyaltyField::FORMAT_MULTI:
                return (array)$value;

            default:
                throw new RuntimeException("unsupported field format: {$format}");
        }
    }

    /**
     * Parse a date/time field value obtained via the Hey Loyalty API and return a UNIX timestamp value.
     *
     * @param string $value
     *
     * @return int|null timestamp
     */
    public function parseDateTime($value)
    {
        static $utc;

        if (!isset($utc)) {
            $utc = new DateTimeZone('UTC');
        }

        if ($value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return null; // not a valid date
        }

        if (strlen($value) === 10) {
            return DateTime::createFromFormat('Y-m-d', $value, $utc)->getTimestamp();
        } else {
            return DateTime::createFromFormat('Y-m-d H:i:s', $value, $utc)->getTimestamp();
        }
    }

    /**
     * Format a UNIX timestamp value for use with the Hey Loyalty API.
     *
     * @param int $value timestamp
     *
     * @return string
     */
    public function formatDateTime($value)
    {
        return $value ? gmdate('Y-m-d', $value) : null;
    }
}
