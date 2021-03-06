<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class AddLanguages extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('languages', ['id' => false, 'primary_key' => ['id'], 'engine' => 'InnoDB', 'encoding' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'comment' => '', 'row_format' => 'Dynamic']);
        $table->addColumn('id', 'string', ['null' => false, 'limit' => 2, 'collation' => 'utf8mb4_unicode_ci', 'encoding' => 'utf8mb4'])
            ->addColumn('name', 'string', ['null' => false, 'limit' => 45, 'collation' => 'utf8mb4_unicode_ci', 'encoding' => 'utf8mb4', 'after' => 'id'])
            ->addColumn('title', 'string', ['null' => false, 'limit' => 45, 'collation' => 'utf8mb4_unicode_ci', 'encoding' => 'utf8mb4', 'after' => 'name'])
            ->addColumn('order', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3, 'signed' => false, 'after' => 'title'])
            ->addColumn('created_at', 'datetime', ['null' => true, 'after' => 'order'])
            ->addColumn('updated_at', 'datetime', ['null' => true, 'after' => 'created_at'])
            ->addColumn('is_deleted', 'boolean', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3, 'after' => 'updated_at'])
            ->save();

     
        $table = $this->table('users');
        $table->addColumn('roles_id', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'after' => 'lastname'])->save();
        $table->save();
        if ($this->table('users')->hasColumn('user_role')) {
            $this->table('users')->removeColumn('user_role')->update();
        }
    }
}
