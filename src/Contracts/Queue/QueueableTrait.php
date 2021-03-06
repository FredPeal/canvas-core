<?php

declare(strict_types=1);

namespace Canvas\Contracts\Queue;

use Canvas\Queue\Queue;

trait QueueableTrait
{
    /**
     * The name of the queue the job should be sent to.
     *
     * @var string
     */
    public $queue = Queue::JOBS;

    /**
     * Set the desired queue for the job.
     *
     * @param  string $queue
     * @return $this
     */
    public function onQueue(string $queue)
    {
        $this->queue = $queue;
        return $this;
    }
}
