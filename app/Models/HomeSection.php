<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HomeSection extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'home_sections';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'section_type',
        'title',
        'subtitle',
        'content',
        'data',
        'background_color',
        'text_color',
        'image',
        'order',
        'is_active'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Available section types
     *
     * @var array
     */
    const SECTION_TYPES = [
        'services' => 'Services Section',
        'stats' => 'Statistics Section',
        'cta' => 'Call to Action Section',
        'dashboard' => 'Dashboard Section',
        'content' => 'Content Section',
        'features' => 'Features Section',
    ];

    /**
     * Get all items belonging to this section (only active)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(SectionItem::class)->where('is_active', true)->orderBy('order');
    }

    /**
     * Get all items including inactive ones
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function allItems(): HasMany
    {
        return $this->hasMany(SectionItem::class)->orderBy('order');
    }

    /**
     * Scope a query to only include active sections
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('order');
    }

    /**
     * Scope a query to filter by section type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('section_type', $type);
    }

    /**
     * Get the full URL for the image
     *
     * @return string|null
     */
    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }
        
        // If it's already a full URL
        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }
        
        // Otherwise, prepend the asset path
        return asset($this->image);
    }

    /**
     * Get the section type label
     *
     * @return string
     */
    public function getSectionTypeLabelAttribute()
    {
        return self::SECTION_TYPES[$this->section_type] ?? ucfirst($this->section_type);
    }

    /**
     * Get a specific data value by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getData($key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Set a specific data value by key
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setData($key, $value)
    {
        $data = $this->data ?? [];
        $data[$key] = $value;
        $this->data = $data;
        return $this;
    }

    /**
     * Move section up in order
     *
     * @return $this
     */
    public function moveUp()
    {
        $previousSection = static::where('order', '<', $this->order)
            ->orderBy('order', 'desc')
            ->first();

        if ($previousSection) {
            $tempOrder = $this->order;
            $this->order = $previousSection->order;
            $previousSection->order = $tempOrder;
            
            $this->save();
            $previousSection->save();
        }

        return $this;
    }

    /**
     * Move section down in order
     *
     * @return $this
     */
    public function moveDown()
    {
        $nextSection = static::where('order', '>', $this->order)
            ->orderBy('order', 'asc')
            ->first();

        if ($nextSection) {
            $tempOrder = $this->order;
            $this->order = $nextSection->order;
            $nextSection->order = $tempOrder;
            
            $this->save();
            $nextSection->save();
        }

        return $this;
    }

    /**
     * Check if section has items
     *
     * @return bool
     */
    public function hasItems()
    {
        return $this->items()->count() > 0;
    }

    /**
     * Get items count
     *
     * @return int
     */
    public function getItemsCountAttribute()
    {
        return $this->items()->count();
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-increment order on creation
        static::creating(function ($section) {
            if (is_null($section->order)) {
                $section->order = static::max('order') + 1;
            }
        });

        // Delete related items when section is deleted
        static::deleting(function ($section) {
            $section->allItems()->delete();
        });
    }
}