<?php

namespace mindplay\heyloyalty;

use RuntimeException;

/**
 * This class represents an individual Hey Loyalty List Member.
 *
 * @property string $id Hey Loyalty Member GUID
 * @property string $status membership status; one of the STATUS_ constants
 * @property mixed $status_email TODO field type and constants?
 * @property mixed $status_mobile TODO field type and constants?
 * @property int $sent_mail number of e-mail sent to this member
 * @property int $sent_sms number of SMS sent to this member
 * @property int $open_rate percentage of e-mails opened by this member
 * @property bool $imported true, if this member was imported
 * @property int $created_at the time at which this member was created (UNIX timestamp)
 * @property int $updated_at the time at which this member was last updated (UNIX timestamp)
 * @property int $postal_code ZIP/postal code
 * @property int $sex member sex; one of the SEX_* constants
 * @property string $email e-mail address
 * @property string $lastname member last name
 * @property string $firstname member first name
 * @property string $password
 * @property string $reference
 * @property int $country country ID
 * @property string $city city name
 * @property string $address street address
 * @property int $birthdate members date of birth (UNIX timestamp)
 * @property string $mobile mobile phone number
 */
class HeyLoyaltyMember
{
    // constants for use with the $status property:

    const STATUS_ACTIVE = 'active'; // TODO other status types?

    // constants for use with the $sex property:

    const SEX_MALE = 1;
    const SEX_FEMALE = 2;

    /**
     * @var mixed[] map of field values, where field name => value
     */
    private $_values = array();

    /**
     * @param string $name
     *
     * @return mixed
     *
     * @ignore
     */
    public function __get($name)
    {
        return @$this->_values[$name];
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return void
     *
     * @ignore
     */
    public function __set($name, $value)
    {
        $this->_values[$name] = $value;
    }
}
