<?php

namespace Canvas\Contracts\Notifications;

use Namshi\Notificator\NotificationInterface;

interface PushNotificationsInterface extends NotificationInterface
{
    /**
     * Assemble Notification
     */
    public function assemble();
}
