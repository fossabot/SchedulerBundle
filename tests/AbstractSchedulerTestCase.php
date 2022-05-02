<?php

declare(strict_types=1);

namespace Tests\SchedulerBundle;

use DateTimeZone;
use Exception;
use PHPUnit\Framework\TestCase;
use SchedulerBundle\FiberScheduler;
use SchedulerBundle\Scheduler;
use SchedulerBundle\SchedulerInterface;
use SchedulerBundle\Task\NullTask;
use SchedulerBundle\Task\TaskInterface;
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
        $fooTask = $tasks->get('foo');
        self::assertCount(1, $tasks);
        self::assertInstanceOf(DateTimeZone::class, $fooTask->getTimezone());
        self::assertSame('Europe/Paris', $fooTask->getName());
    }

    /**
     * @throws Throwable {@see Scheduler::__construct()}
     */
    public function testSchedulerCanRebootWithEmptyTasks(): void
    {
        $scheduler = $this->buildScheduler();

        $scheduler->schedule(new NullTask('bar'));
        self::assertCount(1, $scheduler->getTasks());

        $scheduler->reboot();
        self::assertCount(0, $scheduler->getTasks());
    }

    /**
     * @throws Throwable {@see Scheduler::__construct()}
     */
    public function testSchedulerCanReboot(): void
    {
        $scheduler = $this->buildScheduler();

        $scheduler->schedule(new NullTask('foo', [
            'expression' => '@reboot',
        ]));
        $scheduler->schedule(new NullTask('bar'));
        self::assertCount(2, $scheduler->getTasks());

        $scheduler->reboot();
        self::assertCount(1, $scheduler->getTasks());
    }

    /**
     * @throws Throwable {@see Scheduler::__construct()}
     * @throws Throwable {@see SchedulerInterface::schedule()}
     */
    public function testSchedulerCannotPreemptEmptyDueTasks(): void
    {
        $task = new NullTask('foo');

        $scheduler = $this->buildScheduler();

        $scheduler->preempt('foo', static fn (TaskInterface $task): bool => $task->getName() === 'bar');
        self::assertNotSame(TaskInterface::READY_TO_EXECUTE, $task->getState());
    }

    /**
     * @throws Exception {@see Scheduler::__construct()}
     * @throws Throwable {@see FiberScheduler::getTimezone()}
     */
    public function testSchedulerCanReturnTheTimezone(): void
    {
        $scheduler = $this->buildScheduler();

        $timezone = $scheduler->getTimezone();
        self::assertSame('UTC', $timezone->getName());
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
