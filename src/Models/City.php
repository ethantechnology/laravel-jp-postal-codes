<?php

namespace Eta\JpPostalCodes\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
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
        $this->setTable(config('jp-postal-codes.tables.cities', 'cities'));
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'prefecture_code',
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
     * Get the prefecture that this city belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function prefecture(): BelongsTo
    {
        return $this->belongsTo(Prefecture::class, 'prefecture_code', 'code');
    }

    /**
     * Get the postal codes for this city.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function postalCodes(): HasMany
    {
        return $this->hasMany(PostalCode::class, 'city_code', 'code');
    }
} 