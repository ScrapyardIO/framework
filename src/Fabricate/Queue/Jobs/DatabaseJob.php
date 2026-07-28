<?php

namespace Fabricate\Queue\Jobs;

use Fabricate\Chassis\Chassis;
use Fabricate\Contracts\Queue\Job as JobContract;
use Fabricate\Queue\DatabaseQueue;

class DatabaseJob extends Job implements JobContract
{
    /**
     * The database queue instance.
     *
     * @var \Fabricate\Queue\DatabaseQueue
     */
    protected $database;

    /**
     * The database job payload.
     *
     * @var \Fabricate\Queue\Jobs\DatabaseJobRecord
     */
    protected $job;

    /**
     * Create a new job instance.
     *
     * @param  \Fabricate\Chassis\Chassis  $container
     * @param  \Fabricate\Queue\DatabaseQueue  $database
     * @param  \Fabricate\Queue\Jobs\DatabaseJobRecord  $job
     * @param  string  $connectionName
     * @param  string  $queue
     */
    public function __construct(Chassis $container, DatabaseQueue $database, $job, $connectionName, $queue)
    {
        $this->job = $job;
        $this->queue = $queue;
        $this->database = $database;
        $this->container = $container;
        $this->connectionName = $connectionName;
    }

    /**
     * Release the job back into the queue after (n) seconds.
     *
     * @param  int  $delay
     * @return void
     */
    public function release($delay = 0)
    {
        parent::release($delay);

        $this->database->deleteAndRelease($this->queue, $this, $delay);
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        parent::delete();

        $this->database->deleteReserved($this->queue, $this->job->id);
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return (int) $this->job->attempts;
    }

    /**
     * Get the job identifier.
     *
     * @return string|int
     */
    public function getJobId()
    {
        return $this->job->id;
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->job->payload;
    }

    /**
     * Get the database job record.
     *
     * @return \Fabricate\Queue\Jobs\DatabaseJobRecord
     */
    public function getJobRecord()
    {
        return $this->job;
    }
}
