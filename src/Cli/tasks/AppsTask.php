<?php

namespace Canvas\Cli\Tasks;

use Phalcon\Cli\Task as PhTask;
use Canvas\Models\Apps;
use Phalcon\Security\Random;

/**
 * Class AclTask
 *
 * @package Canvas\Cli\Tasks;
 *
 * @property \Canvas\Acl\Manager $acl
 */
class AppsTask extends PhTask
{
    /**
     * Create the default roles of the system
     *
     * @return void
     */
    public function createAction(array $params): void
    {
        $random = new Random();
        $appName = $params[0];
        $appDescription = $params[1];
        //Create a new app
        $app = new Apps();
        $app->name = $appName;
        $app->description = $appDescription;
        $app->key = $random->uuid();
        $app->is_public = 1;
        $app->default_apps_plan_id = 1;
        $app->created_at = date('Y-m-d H:i:s');
        $app->is_deleted = 0;
        $app->payments_active = 0;

        if (!$app->save()) {
            die('App could not be created');
        }

        $this->acl->setApp($app);

        // $this->acl->addRole('Default.Admins');
        print_r($this->acl->addRole($appName .'.Admins'));
        die();
        $this->acl->addRole($appName .'.Agents');
        $this->acl->addRole($appName .'.Users');

        $this->acl->addResource($appName .'.Users', ['read', 'list', 'create', 'update', 'delete']);
        $this->acl->allow('Admins', $appName .'.Users', ['read', 'list', 'create', 'update', 'delete']);
        //$this->acl->deny('Admins', 'Default.Users', []);
    }
}
