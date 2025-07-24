<?php

namespace Eta\JpPostalCodes\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prefecture extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Create a new model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        
        // Set table name from config
        $this->setTable(config('jp-postal-codes.tables.prefectures', 'prefectures'));
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'name'
    ];

    /**
     * The attributes that should not be mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'deleted_at',
    ];

    /**
     * Get the postal codes for the prefecture.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function postalCodes(): HasMany
    {
        return $this->hasMany(PostalCode::class, 'prefecture_code', 'code');
    }

    /**
     * Get the cities for the prefecture.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'prefecture_code', 'code');
    }
} 