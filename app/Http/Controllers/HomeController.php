<?php

namespace App\Http\Controllers;

use App\Models\SliderVideo;
use App\Models\HomeSection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Display the homepage with dynamic sections
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        // Get the first active slider video
        $sliderVideos = SliderVideo::active()->get();

        // Get all active home sections with their items
        $sections = HomeSection::active()->with('items')->get();

        // Optional: Get specific sections if you need them separately
        // $servicesSection = HomeSection::ofType('services')->active()->first();
        // $statsSection = HomeSection::ofType('stats')->active()->first();

        return view('home', compact('sliderVideos', 'sections'));
    }

    /**
     * Show a preview of the homepage (useful for admin preview)
     *
     * @return \Illuminate\View\View
     */
    public function preview(): View
    {
        // Get all sections including inactive ones for preview
        $sliderVideos = SliderVideo::orderBy('order')->get();
        $sections = HomeSection::orderBy('order')->with('allItems')->get();

        return view('home', compact('sliderVideos', 'sections'));
    }

    /**
     * Get homepage data as JSON (useful for API)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function json()
    {
        $sliderVideos = SliderVideo::active()->get();
        $sections = HomeSection::active()->with('items')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'hero' => $sliderVideos->first(),
                'sections' => $sections
            ]
        ]);
    }

    /**
     * Get a specific section by ID or type
     *
     * @param string $identifier (can be ID or section_type)
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSection($identifier)
    {
        // Try to find by ID first
        if (is_numeric($identifier)) {
            $section = HomeSection::with('items')->find($identifier);
        } else {
            // Otherwise find by section_type
            $section = HomeSection::ofType($identifier)->active()->with('items')->first();
        }

        if (!$section) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $section
        ]);
    }
}
