<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * 各ユーザーへ初期お気に入りを登録する。
     */
    public function run(): void
    {
        $users = User::all()->keyBy('email');
        $books = Book::all()->keyBy('isbn');

        $favorites = [
            'yamada@example.com' => [
                '9784422100524',
                '9784873115658',
                '9784309226712',
                '9784822289607',
            ],
            'suzuki@example.com' => [
                '9784101010014',
                '9784863940246',
                '9784478025819',
            ],
            'tanaka@example.com' => [
                '9784873115658',
                '9784048930598',
                '9784822251468',
                '9784822289607',
            ],
            'sato@example.com' => [
                '9784101010021',
                '9784309226712',
                '9784163902302',
            ],
            'takahashi@example.com' => [
                '9784422100524',
                '9784863940246',
                '9784048930598',
                '9784478025819',
                '9784163902302',
            ],
        ];

        collect($favorites)->each(function (array $bookIsbns, string $userEmail) use ($users, $books): void {
            $bookIds = collect($bookIsbns)
                ->map(fn (string $isbn): int => $books->get($isbn)->id)
                ->all();

            $users->get($userEmail)
                ->favoriteBooks()
                ->syncWithoutDetaching($bookIds);
        });
    }
}
