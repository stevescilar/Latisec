<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'home_section_id',
        'title',
        'description',
        'icon',
        'image',
        'link',
        'data',
        'order',
        'is_active'
    ];

    protected $casts = [
        'data' => 'array',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the home section this item belongs to
     */
    public function homeSection(): BelongsTo
    {
        return $this->belongsTo(HomeSection::class);
    }

    /**
     * Scope to get only active items ordered by order field
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('order');
    }

    /**
     * Scope to filter by section
     */
    public function scopeForSection($query, $sectionId)
    {
        return $query->where('home_section_id', $sectionId);
    }

    /**
     * Get the full URL for the image
     */
    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }
        
        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }
        
        return asset($this->image);
    }

    /**
     * Get the full URL for the icon (if it's an image)
     */
    public function getIconUrlAttribute()
    {
        if (!$this->icon) {
            return null;
        }

        // If it's a Font Awesome class or similar, return as is
        if (str_starts_with($this->icon, 'fa') || str_starts_with($this->icon, 'icon-')) {
            return $this->icon;
        }
        
        // If it's a URL
        if (filter_var($this->icon, FILTER_VALIDATE_URL)) {
            return $this->icon;
        }
        
        // Otherwise treat as a file path
        return asset($this->icon);
    }

    /**
     * Check if icon is an image or font class
     */
    public function isIconImage()
    {
        if (!$this->icon) {
            return false;
        }

        // Check if it's a font awesome class
        if (str_starts_with($this->icon, 'fa') || str_starts_with($this->icon, 'icon-')) {
            return false;
        }

        // Check if it's an image extension
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
        $extension = pathinfo($this->icon, PATHINFO_EXTENSION);
        
        return in_array(strtolower($extension), $imageExtensions);
    }

    /**
     * Get data value by key with default
     */
    public function getData($key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Set data value by key
     */
    public function setData($key, $value)
    {
        $data = $this->data ?? [];
        $data[$key] = $value;
        $this->data = $data;
        return $this;
    }

    /**
     * Move item up in order within its section
     */
    public function moveUp()
    {
        $previousItem = static::where('home_section_id', $this->home_section_id)
            ->where('order', '<', $this->order)
            ->orderBy('order', 'desc')
            ->first();

        if ($previousItem) {
            $tempOrder = $this->order;
            $this->order = $previousItem->order;
            $previousItem->order = $tempOrder;
            
            $this->save();
            $previousItem->save();
        }

        return $this;
    }

    /**
     * Move item down in order within its section
     */
    public function moveDown()
    {
        $nextItem = static::where('home_section_id', $this->home_section_id)
            ->where('order', '>', $this->order)
            ->orderBy('order', 'asc')
            ->first();

        if ($nextItem) {
            $tempOrder = $this->order;
            $this->order = $nextItem->order;
            $nextItem->order = $tempOrder;
            
            $this->save();
            $nextItem->save();
        }

        return $this;
    }

    /**
     * Get the button style from data
     */
    public function getButtonStyle()
    {
        return $this->getData('button_style', 'btn-primary');
    }

    /**
     * Boot method to set order automatically
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if (is_null($item->order)) {
                $maxOrder = static::where('home_section_id', $item->home_section_id)->max('order');
                $item->order = $maxOrder ? $maxOrder + 1 : 1;
            }
        });
    }
}