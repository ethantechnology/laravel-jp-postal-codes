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
            $table->id();
            $table->string('code', 20)->unique()->comment('全国地方公共団体コード');
            $table->string('prefecture_code', 10)->comment('都道府県コード');
            $table->string('name', 50)->comment('市区町村名');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('code');
            $table->index('prefecture_code');
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