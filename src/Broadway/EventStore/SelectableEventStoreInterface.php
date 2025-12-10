<?php

namespace Cosmologist\Gears\Broadway\EventStore;

use Doctrine\Common\Collections\Criteria;

/**
 * An interface that allows you to select messages from the storage based on specified criteria.
 *
 * The criteria specified using `Doctrine\Common\Collections\Criteria`.
 */
interface SelectableEventStoreInterface
{
    /**
     * Selects messages based on $criteria and passes them to $callback
     */
    public function walk(Criteria $criteria, callable $callback): void;
}
