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
        Schema::create('jp_prefectures', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique()->comment('都道府県コード');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jp_prefectures');
    }
}; 