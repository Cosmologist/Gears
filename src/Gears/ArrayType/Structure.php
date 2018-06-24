<?php

namespace Cosmologist\Gears\ArrayType;

/**
 * Collection of commonly used methods for working with hierarchical structures
 */
class Structure
{
    /**
     * Convert a list (a flat array) to a hierarchical array using the parent key
     *
     * @link http://blog.tekerson.com/2009/03/03/converting-a-flat-array-with-parent-ids-to-a-nested-tree/
     *
     * @param array  $list      List
     * @param string $indexKey  Key name for the element containing the item ID
     * @param string $parentKey Key name for the element containing the parent item ID
     * @param string $childKey  Key name for the element for placement children
     *
     * @return array
     */
    public static function convertListToTree(array $list, $indexKey = 'id', $parentKey = 'parent_id', $childKey = 'children')
    {
        $indexed = array();

        // first pass - get the array indexed by the primary id
        foreach ($list as $row) {
            $indexed[$row[$indexKey]]            = $row;
            $indexed[$row[$indexKey]][$childKey] = [];
        }

        // second pass
        $root = [];
        foreach ($indexed as $id => $row) {
            $indexed[$row[$parentKey]][$childKey][$id] = &$indexed[$id];
            if (!$row[$parentKey]) {
                $root[$id] = &$indexed[$id];
            }
        }

        return $root;
    }
}