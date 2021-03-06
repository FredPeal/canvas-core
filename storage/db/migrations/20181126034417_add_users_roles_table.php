    <?php

    use Phinx\Migration\AbstractMigration;
    use Phinx\Db\Adapter\MysqlAdapter;

    class AddUsersRolesTable extends AbstractMigration
    {
        public function change()
        {
            $table = $this->table('user_roles', ['id' => false, 'primary_key' => ['users_id', 'apps_id', 'companies_id'], 'engine' => 'InnoDB', 'encoding' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'comment' => '', 'row_format' => 'Dynamic']);
            $table->addColumn('users_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
                ->addColumn('roles_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'after' => 'users_id'])
                ->addColumn('apps_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'after' => 'roles_id'])
                ->addColumn('companies_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'after' => 'apps_id'])
                ->addColumn('created_at', 'datetime', ['null' => false, 'after' => 'companies_id'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'after' => 'created_at'])
                ->addColumn('is_deleted', 'boolean', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3, 'after' => 'updated_at'])
                ->save();
        }
    }
