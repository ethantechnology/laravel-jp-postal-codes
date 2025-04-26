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
        Schema::create('jp_postal_codes', function (Blueprint $table) {
            $table->id();
            $table->string('address_code')->nullable();
            $table->string('prefecture_code')->nullable();
            $table->string('city_code')->nullable();
            $table->string('area_code')->nullable();
            $table->string('postal_code');
            $table->boolean('is_office')->default(false);
            $table->boolean('is_closed')->default(false);
            $table->string('prefecture')->nullable();
            $table->string('prefecture_kana')->nullable();
            $table->string('city')->nullable();
            $table->string('city_kana')->nullable();
            $table->string('area')->nullable();
            $table->string('area_kana')->nullable();
            $table->string('area_info')->nullable();
            $table->string('kyoto_road_name')->nullable();
            $table->string('chome')->nullable();
            $table->string('chome_kana')->nullable();
            $table->string('info')->nullable();
            $table->string('office_name')->nullable();
            $table->string('office_name_kana')->nullable();
            $table->string('office_address')->nullable();
            $table->string('new_address_code')->nullable();
            $table->timestamps();

            $table->index('postal_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jp_postal_codes');
    }
}; 