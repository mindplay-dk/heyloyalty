<?php

namespace mindplay\heyloyalty;

/**
 * This class represents a single Field in a Hey Loyalty List.
 *
 * @see HeyLoyaltyList::$fields
 */
class HeyLoyaltyField
{
    // constants for use with $type:

	const TYPE_FIXED = 'fixed';
	const TYPE_CUSTOM = 'custom';

    // constants for use with $format:

	const FORMAT_TEXT = 'text';
	const FORMAT_CHOICE = 'choice';
	const FORMAT_DATE = 'date';
	const FORMAT_NUMBER = 'number';
	const FORMAT_PASSWORD = 'password';
	const FORMAT_BOOLEAN = 'boolean';
	const FORMAT_MULTI = 'multi';

	/** @var int */
	public $id;

	/** @var int */
	public $list_id;

	/** @var bool */
	public $required_in_shop;

	/** @var mixed fallback value when null */
	public $fallback;

	/** @var string see TYPE_* constants */
	public $type;

	/** @var int TODO */
	public $type_id;

	/** @var string field input format; see FORMAT_* constants */
	public $format;

	/** @var string field name */
	public $name;

	/** @var string field label */
	public $label;

	/** @var string[] map where option ID (int) => option label */
	public $options = array();
}
