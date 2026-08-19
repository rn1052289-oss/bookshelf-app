<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    /**
     * ログインユーザーのお気に入り書籍一覧を表示する。
     */
    public function index(Request $request): View
    {
        $books = $request->user()
            ->favoriteBooks()
            ->paginate(10);

        return view('favorites.index', compact('books'));
    }

    /**
     * 書籍のお気に入り登録・解除を切り替える。
     */
    public function toggle(Request $request, Book $book): RedirectResponse
    {
        $request->user()
            ->favoriteBooks()
            ->toggle($book->id);

        return back();
    }
}
