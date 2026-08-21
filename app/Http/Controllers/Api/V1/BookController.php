<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexBookRequest;
use App\Http\Requests\Api\V1\StoreBookRequest;
use App\Http\Requests\Api\V1\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    /**
     * 書籍一覧を取得する。
     */
    public function index(IndexBookRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (isset($validated['genre'])) {
            $genreExists = Genre::whereKey($validated['genre'])->exists();

            if (! $genreExists) {
                return response()->json([
                    'error' => 'ジャンルが見つかりませんでした。',
                ], 404);
            }
        }

        $books = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when(
                isset($validated['keyword']),
                function ($query) use ($validated) {
                    $query->where(function ($query) use ($validated) {
                        $query->where('title', 'like', '%'.$validated['keyword'].'%')
                            ->orWhere('author', 'like', '%'.$validated['keyword'].'%');
                    });
                }
            )
            ->when(
                isset($validated['genre']),
                function ($query) use ($validated) {
                    $query->whereHas('genres', function ($query) use ($validated) {
                        $query->where('genres.id', $validated['genre']);
                    });
                }
            )
            ->latest()
            ->paginate($validated['per_page'] ?? 20);

        return response()->json([
            'data' => BookResource::collection($books->items()),
            'meta' => [
                'current_page' => $books->currentPage(),
                'last_page' => $books->lastPage(),
                'per_page' => $books->perPage(),
                'total' => $books->total(),
            ],
        ]);
    }

    /**
     * 書籍詳細を取得する。
     */
    public function show(Book $book): BookResource
    {
        $book->load([
            'genres',
            'reviews.user',
        ]);

        return new BookResource($book);
    }

    /**
     * 書籍を登録する。
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $genreIds = $validated['genre_ids'];
        unset($validated['genre_ids']);

        $book = DB::transaction(function () use ($validated, $genreIds) {
            $book = Book::create($validated);

            $book->genres()->sync($genreIds);

            return $book;
        });

        $book->load('genres');

        return (new BookResource($book))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 書籍を更新する。
     */
    public function update(UpdateBookRequest $request, Book $book): JsonResponse
    {
        $validated = $request->validated();

        $genreIds = $validated['genre_ids'];
        unset($validated['genre_ids']);

        DB::transaction(function () use ($book, $validated, $genreIds) {
            $book->update($validated);

            $book->genres()->sync($genreIds);
        });

        $book->load('genres');

        return (new BookResource($book))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * 書籍を削除する。
     */
    public function destroy(Book $book): JsonResponse
    {
        DB::transaction(function () use ($book) {
            $book->delete();
        });

        return response()->json(null, 204);
    }
}
