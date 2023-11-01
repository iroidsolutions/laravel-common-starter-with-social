<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable(); //for social login used nullable password
            $table->string('provider')->nullable();         //for social login 
            $table->string('provider_id')->nullable();         //for social login 
            $table->string('is_social')->default(0)->comment('1 for social account/0 for normal account'); //
            $table->string('profile_pic')->nullable();
            $table->string('time_zone')->nullable()->default('UTC');;
            $table->timestamp('last_login')->useCurrent = true;;
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
