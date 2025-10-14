<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
public function up()
{
    DB::statement("ALTER TABLE orders MODIFY COLUMN status 
        ENUM('unpaid','paid','shipped','done','expired','cancelled') DEFAULT 'unpaid'");
}

public function down()
{
    DB::statement("ALTER TABLE orders MODIFY COLUMN status 
        ENUM('unpaid','paid','shipped','done') DEFAULT 'unpaid'");
}

};
