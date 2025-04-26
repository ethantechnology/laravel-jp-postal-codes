<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jp_cities', function (Blueprint $table) {
            $table->string('id', 20)->primary()->comment('全国地方公共団体コード');
            $table->unsignedBigInteger('prefecture_id')->comment('都道府県ID');
            $table->string('name', 50)->comment('市区町村名');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('prefecture_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jp_cities');
    }
}; 