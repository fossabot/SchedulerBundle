<?php

declare(strict_types=1);

namespace SchedulerBundle\Task;

use Symfony\Component\Stopwatch\Stopwatch;
use function memory_get_usage;
use function sprintf;

/**
 * @author Guillaume Loulier <contact@guillaumeloulier.fr>
 */
final class TaskExecutionTracker implements TaskExecutionTrackerInterface
{
    private $watch;

    public function __construct(Stopwatch $watch)
    {
        $this->watch = $watch;
    }

    /**
     * {@inheritdoc}
     */
    public function startTracking(TaskInterface $task): void
    {
        if (!$task->isTracked()) {
            return;
        }

        $this->watch->start(sprintf('task_execution.%s', $task->getName()));
    }

    /**
     * {@inheritdoc}
     */
    public function endTracking(TaskInterface $task): void
    {
        if (!$task->isTracked()) {
            return;
        }

        $task->setExecutionMemoryUsage(memory_get_usage());

        if (!$this->watch->isStarted(sprintf('task_execution.%s', $task->getName()))) {
            return;
        }

        $event = $this->watch->stop(sprintf('task_execution.%s', $task->getName()));
        $task->setExecutionComputationTime($event->getDuration());
    }
}
