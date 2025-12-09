<?php

namespace Cosmologist\Gears\Broadway\Event;

use Commerce\Bundle\TaskBundle\Event\TaskProcessEventInterface;
use Commerce\Bundle\TaskBundle\Process\AbstractTask;
use Cosmologist\Gears\ValueObject\IdentifierAbstract;

/**
 * Use this interface to highlight the relationship of an event to other aggregates.
 *
 * This will allow you to conveniently select related events from the repository.
 *
 * ```
 * readonly class TaskWasCreatedEvent implements RelatedProcessEventInterface
 * {
 *     public array $_related;
 *
 *     public function __construct(public AbstractTask $task)
 *     {
 *         $this->_related = array_map(fn(IdentifierAbstract $identifier) => $identifier->getValue(), $this->task->getRelations());
 *     }
 * }
 * ```
 */
interface RelatedProcessEventInterface
{
    /** @var string[] */
    public array $_related {
        get;
    }
}
