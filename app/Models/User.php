<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * ユーザーが登録した書籍を取得する。
     */
    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    /**
     * ユーザーが投稿したレビューを取得する。
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * ユーザーがお気に入り登録した書籍を取得する。
     */
    public function favoriteBooks(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'favorites')->withTimestamps();
    }

    /**
     * ユーザーがいいねしたレビューを取得する。
     */
    public function likedReviews(): BelongsToMany
    {
        return $this->belongsToMany(Review::class, 'review_likes')->withTimestamps();
    }

    /**
     * ユーザーの読書計画を取得する。
     */
    public function readingPlans(): HasMany
    {
        return $this->hasMany(ReadingPlan::class);
    }

    /**
     * ユーザー自身のレビューからマイ読書レポートを集計する。
     */
    public function readingReportStats(): array
    {
        $reviews = $this->reviews()
            ->with('book.genres')
            ->get();

        $summary = [
            'total_reviews' => $reviews->count(),
            'books_read' => $reviews->pluck('book_id')->unique()->count(),
            'average_rating' => floatval($reviews->avg('rating') ?? 0),
        ];

        $ratingDistribution = collect(range(1, 5))
            ->map(fn (int $rating): int => $reviews->where('rating', $rating)->count());

        $topRatedBooks = $reviews
            ->groupBy('book_id')
            ->map(function ($bookReviews) {
                $book = $bookReviews->first()->book;
                $averageRating = floatval($bookReviews->avg('rating'));

                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'average_rating' => $averageRating,
                    'review_count' => $bookReviews->count(),
                    'created_at' => $book->created_at,
                    'rating' => (int) round($averageRating),
                ];
            })
            ->filter(fn (array $book): bool => $book['average_rating'] >= 4.0)
            ->sortBy([
                ['average_rating', 'desc'],
                ['review_count', 'desc'],
                ['created_at', 'desc'],
            ])
            ->take(5)
            ->values();

        $genreRatings = $reviews
            ->flatMap(function ($review) {
                return $review->book->genres->map(function ($genre) use ($review) {
                    return [
                        'id' => $genre->id,
                        'name' => $genre->name,
                        'rating' => $review->rating,
                    ];
                });
            })
            ->groupBy('id')
            ->map(function ($genreReviews) {
                return [
                    'id' => $genreReviews->first()['id'],
                    'name' => $genreReviews->first()['name'],
                    'average_rating' => floatval($genreReviews->avg('rating')),
                    'count' => $genreReviews->count(),
                ];
            })
            ->sortBy([
                ['average_rating', 'desc'],
                ['count', 'desc'],
                ['name', 'asc'],
            ])
            ->take(5)
            ->values();

        return [
            'summary' => $summary,
            'rating_distribution' => $ratingDistribution,
            'top_rated_books' => $topRatedBooks,
            'genre_ratings' => $genreRatings,
        ];
    }
}
