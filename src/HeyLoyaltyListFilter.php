<?php

namespace mindplay\heyloyalty;

/**
 * This class represents a set of filter options for list member queries.
 */
class HeyLoyaltyListFilter
{
    private $filters = array();

    /**
     * @param string $field_name
     * @param string|int|bool $value
     */
    public function equalTo($field_name, $value)
    {
        $this->filters["filter[{$field_name}][eq][]"] = (string) $value;
    }

    /**
     * @param string $field_name
     * @param string|int|bool $value
     */
    public function notEqualTo($field_name, $value)
    {
        $this->filters["filter[{$field_name}][neq][]"] = (string) $value;
    }

    // TODO build out remaining filter functions

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->filters;
    }
}
