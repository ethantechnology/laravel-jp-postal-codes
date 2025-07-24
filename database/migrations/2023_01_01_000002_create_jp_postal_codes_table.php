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
            $table->string('address_code', 20)->comment('全国地方公共団体コード');
            $table->string('prefecture_code', 10)->comment('都道府県コード');
            $table->string('city_code', 20)->comment('市区町村コード');
            $table->string('area_code', 20)->comment('町域コード');
            $table->string('postal_code', 7)->comment('郵便番号');
            $table->boolean('is_office')->default(false)->comment('事業所フラグ');
            $table->boolean('is_closed')->default(false)->comment('廃止フラグ');
            $table->string('prefecture', 10)->comment('都道府県名');
            $table->string('prefecture_kana', 20)->comment('都道府県名カナ');
            $table->string('city', 50)->comment('市区町村名');
            $table->string('city_kana', 100)->comment('市区町村名カナ');
            $table->string('area', 100)->comment('町域名');
            $table->string('area_kana', 100)->comment('町域名カナ');
            $table->string('area_info', 100)->nullable()->comment('町域補足情報');
            $table->string('kyoto_road_name', 100)->nullable()->comment('京都通り名');
            $table->string('chome', 50)->nullable()->comment('丁目');
            $table->string('chome_kana', 50)->nullable()->comment('丁目カナ');
            $table->string('info', 255)->nullable()->comment('補足情報');
            $table->string('office_name', 100)->nullable()->comment('事業所名');
            $table->string('office_name_kana', 100)->nullable()->comment('事業所名カナ');
            $table->string('office_address', 255)->nullable()->comment('事業所住所');
            $table->string('new_address_code', 20)->nullable()->comment('新住所コード');
            $table->timestamps();

            $table->index('postal_code');
            $table->index('prefecture_code');
            $table->index('city_code');
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