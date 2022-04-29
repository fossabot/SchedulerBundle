<?php

declare(strict_types=1);

namespace Tests\SchedulerBundle;

use DateTimeZone;
use Exception;
use PHPUnit\Framework\TestCase;
use SchedulerBundle\Scheduler;
use SchedulerBundle\SchedulerInterface;
use SchedulerBundle\Task\NullTask;
use Throwable;

/**
 * @author Guillaume Loulier <contact@guillaumeloulier.fr>
 */
abstract class AbstractSchedulerTestCase extends TestCase
{
    abstract protected function buildScheduler(): SchedulerInterface;

    /**
     * @throws Exception {@see Scheduler::__construct()}
     * @throws Throwable {@see SchedulerInterface::schedule()}
     */
    public function testSchedulerCanScheduleTasks(): void
    {
        $scheduler = $this->buildScheduler();

        $scheduler->schedule(new NullTask('foo'));
        self::assertCount(1, $scheduler->getTasks());
    }

    /**
     * @throws Exception {@see Scheduler::__construct()}
     * @throws Throwable {@see SchedulerInterface::schedule()}
     */
    public function testSchedulerCanScheduleTasksWithCustomTimezone(): void
    {
        $scheduler = $this->buildScheduler();

        $scheduler->schedule(new NullTask('foo', [
            'timezone' => new DateTimeZone('Europe/Paris'),
        ]));

        $tasks = $scheduler->getTasks();
        self::assertCount(1, $tasks);
        self::assertSame('Europe/Paris', $tasks->get('foo')->getTimezone()->getName());
    }

    /**
     * @throws Exception {@see Scheduler::__construct()}
     * @throws Throwable {@see SchedulerInterface::getPoolConfiguration()}
     */
    public function testSchedulerPoolConfigurationIsAvailable(): void
    {
        $scheduler = $this->buildScheduler();

        $poolConfiguration = $scheduler->getPoolConfiguration();
        self::assertSame('UTC', $poolConfiguration->getTimezone()->getName());
        self::assertArrayNotHasKey('foo', $poolConfiguration->getDueTasks());
        self::assertCount(0, $poolConfiguration->getDueTasks());
    }
}
