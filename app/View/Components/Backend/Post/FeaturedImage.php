<?php

namespace App\View\Components\Backend\Post;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FeaturedImage extends Component
{
    public $metaDatas;
    public $required;
    public $postType;
    public $templateType;
    public $sizes;
    public $recommendedSize;

    /**
     * Recommended featured image sizes, keyed by page template / post type.
     */
    public const SIZES = [
        'default' => '1200 x 630',
        'about' => '350 x 530',
        'team' => '245 x 305',
        'teams' => '380 x 475',
    ];

    /**
     * Create a new component instance.
     */
    public function __construct($metaDatas = NULL, $required = null, $postType = 'post', $templateType = null)
    {
        $this->metaDatas = $metaDatas;
        $this->required = $required;
        $this->postType = $postType;
        $this->templateType = $templateType;
        $this->sizes = self::SIZES;
        $this->recommendedSize = self::SIZES[$templateType] ?? self::SIZES[$postType] ?? self::SIZES['default'];
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.backend.post.featured-image');
    }
}
