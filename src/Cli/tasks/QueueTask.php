<?php

namespace Canvas\Cli\Tasks;

use Canvas\Contracts\Queue\QueueableJobInterfase;
use Phalcon\Cli\Task as PhTask;
use Canvas\Models\Users;
use Canvas\Queue\Queue;
use RuntimeException;
use Canvas\Notifications\Notification;
use Phalcon\Mvc\Model;

/**
 * CLI To send push ontification and pusher msg.
 *
 * @package Canvas\Cli\Tasks
 *
 * @property Config $config
 * @property \Pusher\Pusher $pusher
 * @property \Monolog\Logger $log
 * @property Channel $channel
 * @property Queue $queue
 *
 */
class QueueTask extends PhTask
{
    /**
     * Queue action for mobile notifications.
     * @return void
     */
    public function mainAction(array $params): void
    {
        echo 'Canvas Ecosystem Queue Jobs: events | notifications' . PHP_EOL;
    }

    /**
     * Queue to process internal Canvas Events.
     *
     * @return void
     */
    public function eventsAction()
    {
        $callback = function ($msg) {
            //we get the data from our event trigger and unserialize
            $event = unserialize($msg->body);

            //overwrite the user who is running this process
            if (isset($event['userData']) && $event['userData'] instanceof Users) {
                $this->di->setShared('userData', $event['userData']);
            }

            //lets fire the event
            $this->events->fire($event['event'], $event['source'], $event['data']);

            echo "Event Fired ({$event['event']})  - Process ID " . $msg->delivery_info['consumer_tag'] . PHP_EOL;
            $this->log->info("Notification ({$event['event']}) - Process ID " . $msg->delivery_info['consumer_tag']);
        };

        Queue::process(QUEUE::EVENTS, $callback);
    }

    /**
     * Queue to process internal Canvas Events.
     *
     * @return void
     */
    public function notificationsAction()
    {
        $callback = function ($msg) {
            //we get the data from our event trigger and unserialize
            $notification = unserialize($msg->body);

            //overwrite the user who is running this process
            if ($notification['from'] instanceof Users) {
                $this->di->setShared('userData', $notification['from']);
            }

            if (!$notification['to'] instanceof Users) {
                echo 'Attribute TO has to be a User' . PHP_EOL;
                return;
            }

            if (!class_exists($notification['notification'])) {
                echo 'Attribute notification has to be a Notificatoin' . PHP_EOL;
                return;
            }
            $notificationClass = $notification['notification'];

            if (!$notification['entity'] instanceof Model) {
                echo 'Attribute entity has to be a Model' . PHP_EOL;
                return;
            }

            $user = $notification['to'];

            //instance notification and pass the entity
            $notification = new $notification['notification']($notification['entity']);
            //disable the queue so we process it now
            $notification->disableQueue();

            //run notify for the specifiy user
            $user->notify($notification);

            echo "Notification ({$notificationClass}) sent to {$user->email} - Process ID " . $msg->delivery_info['consumer_tag'] . PHP_EOL;
            $this->log->info("Notification ({$notificationClass}) sent to {$user->email} - Process ID " . $msg->delivery_info['consumer_tag']);
        };

        Queue::process(QUEUE::NOTIFICATIONS, $callback);
    }

    /**
     * Queue to process Canvas Jobs.
     *
     * @return void
     */
    public function jobsAction()
    {
        $callback = function ($msg) {
            //we get the data from our event trigger and unserialize
            $job = unserialize($msg->body);

            //overwrite the user who is running this process
            if ($job['userData'] instanceof Users) {
                $this->di->setShared('userData', $job['from']);
            }

            if (!class_exists($job['class'])) {
                echo 'No Job class found' . PHP_EOL;
                $this->log->error('No Job class found');
                return;
            }

            if (!$job['job'] instanceof QueueableJobInterfase) {
                echo 'This Job is not queable ' . $msg->delivery_info['consumer_tag'] ;
                $this->log->error('This Job is not queable ' . $msg->delivery_info['consumer_tag']);
                return;
            }

            //instance notification and pass the entity
            $job['job']->handle();

            echo "Job ({$job['class']}) ran for {$this->userData->getEmail()} - Process ID " . $msg->delivery_info['consumer_tag'] . PHP_EOL;
            $this->log->info("Job ({$job['class']}) ran for {$this->userData->getEmail()} - Process ID " . $msg->delivery_info['consumer_tag']);
        };

        Queue::process(QUEUE::JOBS, $callback);
    }
}
