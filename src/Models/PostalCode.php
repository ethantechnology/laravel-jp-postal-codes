<?php

namespace Eta\JpPostalCodes\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostalCode extends Model
{
    use HasFactory;

    /**
     * Columns in the postal code table.
     *
     * @var array
     */
    public const COLUMNS = [
        'address_code',
        'prefecture_code',
        'city_code',
        'area_code',
        'postal_code',
        'is_office',
        'is_closed',
        'prefecture',
        'prefecture_kana',
        'city',
        'city_kana',
        'area',
        'area_kana',
        'area_info',
        'kyoto_road_name',
        'chome',
        'chome_kana',
        'info',
        'office_name',
        'office_name_kana',
        'office_address',
        'new_address_code',
    ];

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
        $this->setTable(config('jp-postal-codes.tables.postal_codes', 'jp_postal_codes'));
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = self::COLUMNS;

    /**
     * Get the prefecture that this postal code belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function prefecture(): BelongsTo
    {
        return $this->belongsTo(Prefecture::class, 'prefecture_code', 'id');
    }

    /**
     * Get the city that this postal code belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_code', 'id');
    }

    /**
     * Format postal code by removing hyphens and whitespace for search.
     *
     * @param  string  $postalCode
     * @return string
     */
    public static function normalizePostalCode(string $postalCode): string
    {
        // Remove any existing hyphens and whitespace for search
        return preg_replace('/[\s-]/', '', $postalCode);
    }

    /**
     * 郵便番号一致のスコープ
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string                                 $postalCode
     * @return \Illuminate\Database\Eloquent\Builder  $query
     */
    public function scopePostal($query, $postalCode)
    {
        // Normalize postal code by removing hyphens before search
        $normalizedCode = self::normalizePostalCode($postalCode);
        
        return $query->where('postal_code', $normalizedCode);
    }
} 