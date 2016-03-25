<?php

namespace Cosmologist\Gears\Arr;

/**
 * Class able to convert a flat array with parent ID's to a nested tree
 */
class FlatToTreeConverter
{
    /**
     * Convert a flat array with parent ID's to a nested tree
     *
     * @link http://blog.tekerson.com/2009/03/03/converting-a-flat-array-with-parent-ids-to-a-nested-tree/
     *
     * @param array  $array           Flat array
     * @param string $idKeyName       Key name for the element containing the item ID
     * @param string $parentIdKey     Key name for the element containing the parent item ID
     * @param string $childNodesField Key name for the element for placement children
     *
     * @return array
     */
    public static function convert(array $array, $idKeyName = 'id', $parentIdKey = 'parentId', $childNodesField = 'children')
    {
        $indexed = array();

        // first pass - get the array indexed by the primary id
        foreach ($array as $row) {
            $indexed[$row[$idKeyName]]                   = $row;
            $indexed[$row[$idKeyName]][$childNodesField] = array();
        }

        // second pass
        $root = array();
        foreach ($indexed as $id => $row) {
            $indexed[$row[$parentIdKey]][$childNodesField][$id] = &$indexed[$id];
            if (!$row[$parentIdKey]) {
                $root[$id] = &$indexed[$id];
            }
        }

        return $root;
    }
}