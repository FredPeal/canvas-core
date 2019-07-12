<?php

namespace Canvas\Tests\unit;

use Canvas\Providers\DatabaseProvider;
use Phalcon\Di\FactoryDefault;
use UnitTester;
use Canvas\Models\Apps;

class AppsCest
{
    public function setDatabaseProvider(UnitTester $I){
        $diContainer = new FactoryDefault();
        $provider = new DatabaseProvider();
        $provider->register($diContainer);

        $I->assertTrue($diContainer->has('db'));
    }

    /**
     * @param UnitTester $I
     */
    public function getACLAppTest(UnitTester $I)
    {
        $app = Apps::getACLApp('Default');
        $I->assertTrue($app instanceof Apps);
    }

    /**
     * Validate is an app has an active status or not
     *
     * @param UnitTester $I
     */
    public function isActiveTest(UnitTester $I)
    {
        $app = Apps::getACLApp('Default');
        $I->assertTrue(gettype($app->isActive()) == 'boolean');
    }
}
