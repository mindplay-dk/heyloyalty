<?php

namespace mindplay\heyloyalty;

/**
 * This class represents a Hey Loyalty List.
 */
class HeyLoyaltyList
{
    // constants for use with $duplicates:

    const DUPLICATES_ALLOW = 'allow';
    const DUPLICATES_ALLOW_EMAIL = 'allow_email';
    const DUPLICATES_ALLOW_MOBILE = 'allow_mobile';
    const DUPLICATES_DISALLOW = 'disallow';

    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var int */
    public $country_id;

    // TODO add country model

    /** @var string */
    public $date_format;

    /** @var string whether this list allows duplicate members; one of the DUPLICATES_* constants */
    public $duplicates;

    /** @var HeyLoyaltyField[] map where field name => field instance */
    public $fields = array();
}
