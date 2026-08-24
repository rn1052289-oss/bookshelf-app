<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * マイ読書レポートを表示する。
     */
    public function index(Request $request): View
    {
        $reviews = $request->user()
            ->reviews()
            ->with('book.genres')
            ->get();

        $summary = [
            'total_reviews' => $reviews->count(),
            'books_read' => $reviews->pluck('book_id')->unique()->count(),
            'average_rating' => $reviews->avg('rating') ?? 0,
        ];

        $ratingDistribution = collect(range(1, 5))
            ->map(fn (int $rating): int => $reviews->where('rating', $rating)->count());

        $stats = [
            'summary' => $summary,
            'rating_distribution' => $ratingDistribution,

            // Q2〜Q4の回答後に実装する
            'top_rated_books' => collect(),
            'genre_ratings' => collect(),
        ];

        return view('reports.index', compact('stats'));
    }
}
