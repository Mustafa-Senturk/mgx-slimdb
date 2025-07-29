<?php

use Mgx\Slimdb\Migration\Migration;
use Mgx\Slimdb\TableBlueprint;

class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->create('users', function (TableBlueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email')->unique();
            $table->string('password', 60);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        $this->drop('users');
    }
}