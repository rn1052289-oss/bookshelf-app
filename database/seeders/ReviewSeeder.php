<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * 指定された初期レビュー32件を登録する。
     */
    public function run(): void
    {
        $users = User::all()->keyBy('email');
        $books = Book::all()->keyBy('isbn');

        $reviews = [
            // 吾輩は猫である：3件
            [
                'book_isbn' => '9784101010014',
                'user_email' => 'yamada@example.com',
                'rating' => 5,
                'comment' => '猫の視点から描かれる人間社会が面白く、最後まで楽しく読めました。',
            ],
            [
                'book_isbn' => '9784101010014',
                'user_email' => 'suzuki@example.com',
                'rating' => 4,
                'comment' => '独特な語り口が印象的で、時代を感じながら楽しめました。',
            ],
            [
                'book_isbn' => '9784101010014',
                'user_email' => 'tanaka@example.com',
                'rating' => 3,
                'comment' => '文章は少し難しく感じましたが、ユーモアのある作品でした。',
            ],

            // 人を動かす：3件
            [
                'book_isbn' => '9784422100524',
                'user_email' => 'suzuki@example.com',
                'rating' => 5,
                'comment' => '人との接し方を見直すきっかけになり、仕事にも活かせる内容でした。',
            ],
            [
                'book_isbn' => '9784422100524',
                'user_email' => 'tanaka@example.com',
                'rating' => 4,
                'comment' => '具体例が多く、人間関係について実践的に学べました。',
            ],
            [
                'book_isbn' => '9784422100524',
                'user_email' => 'sato@example.com',
                'rating' => 4,
                'comment' => '昔の本ですが、今でも参考になる考え方が多いと感じました。',
            ],

            // リーダブルコード：3件
            [
                'book_isbn' => '9784873115658',
                'user_email' => 'tanaka@example.com',
                'rating' => 5,
                'comment' => '読みやすいコードを書くための考え方が具体的で、とても参考になりました。',
            ],
            [
                'book_isbn' => '9784873115658',
                'user_email' => 'sato@example.com',
                'rating' => 4,
                'comment' => 'コードを書くときに意識すべきポイントが分かりやすく整理されています。',
            ],
            [
                'book_isbn' => '9784873115658',
                'user_email' => 'takahashi@example.com',
                'rating' => 3,
                'comment' => '基本的な内容も多いですが、復習として役立ちました。',
            ],

            // 7つの習慣：3件
            [
                'book_isbn' => '9784863940246',
                'user_email' => 'sato@example.com',
                'rating' => 5,
                'comment' => '仕事だけでなく日常生活にも活かせる考え方が多くありました。',
            ],
            [
                'book_isbn' => '9784863940246',
                'user_email' => 'takahashi@example.com',
                'rating' => 4,
                'comment' => '自分の行動を振り返るきっかけになる内容でした。',
            ],
            [
                'book_isbn' => '9784863940246',
                'user_email' => 'yamada@example.com',
                'rating' => 4,
                'comment' => '内容は多いですが、じっくり読む価値のある本だと思います。',
            ],

            // 坊っちゃん：3件
            [
                'book_isbn' => '9784101010021',
                'user_email' => 'takahashi@example.com',
                'rating' => 5,
                'comment' => '主人公のまっすぐな性格が魅力的で、テンポよく読めました。',
            ],
            [
                'book_isbn' => '9784101010021',
                'user_email' => 'yamada@example.com',
                'rating' => 4,
                'comment' => '登場人物が個性的で、読みやすい作品でした。',
            ],
            [
                'book_isbn' => '9784101010021',
                'user_email' => 'suzuki@example.com',
                'rating' => 3,
                'comment' => '古い作品ですが、分かりやすく楽しめました。',
            ],

            // サピエンス全史：3件
            [
                'book_isbn' => '9784309226712',
                'user_email' => 'yamada@example.com',
                'rating' => 5,
                'comment' => '人類の歴史を大きな視点から考えることができ、とても興味深かったです。',
            ],
            [
                'book_isbn' => '9784309226712',
                'user_email' => 'tanaka@example.com',
                'rating' => 4,
                'comment' => '歴史だけでなく社会や科学についても考えさせられる内容でした。',
            ],
            [
                'book_isbn' => '9784309226712',
                'user_email' => 'sato@example.com',
                'rating' => 4,
                'comment' => 'ボリュームがありますが、新しい視点を得られる本でした。',
            ],

            // Clean Code：3件
            [
                'book_isbn' => '9784048930598',
                'user_email' => 'suzuki@example.com',
                'rating' => 5,
                'comment' => '保守しやすいコードを書くための原則を深く学べました。',
            ],
            [
                'book_isbn' => '9784048930598',
                'user_email' => 'sato@example.com',
                'rating' => 4,
                'comment' => '実務でコードを書く際に意識したいポイントが多くありました。',
            ],
            [
                'book_isbn' => '9784048930598',
                'user_email' => 'takahashi@example.com',
                'rating' => 4,
                'comment' => '少し難しい部分もありますが、参考になる内容でした。',
            ],

            // 嫌われる勇気：3件
            [
                'book_isbn' => '9784478025819',
                'user_email' => 'tanaka@example.com',
                'rating' => 5,
                'comment' => '物事の捉え方を変えるきっかけになる内容で、とても印象に残りました。',
            ],
            [
                'book_isbn' => '9784478025819',
                'user_email' => 'takahashi@example.com',
                'rating' => 4,
                'comment' => '対話形式なので読みやすく、考え方を理解しやすかったです。',
            ],
            [
                'book_isbn' => '9784478025819',
                'user_email' => 'yamada@example.com',
                'rating' => 3,
                'comment' => '共感できる部分と難しく感じる部分の両方がありました。',
            ],

            // 火花：3件
            [
                'book_isbn' => '9784163902302',
                'user_email' => 'sato@example.com',
                'rating' => 5,
                'comment' => '登場人物の葛藤が丁寧に描かれていて、引き込まれました。',
            ],
            [
                'book_isbn' => '9784163902302',
                'user_email' => 'yamada@example.com',
                'rating' => 4,
                'comment' => '芸人の世界を題材にした人間ドラマとして楽しめました。',
            ],
            [
                'book_isbn' => '9784163902302',
                'user_email' => 'suzuki@example.com',
                'rating' => 4,
                'comment' => '独特な雰囲気があり、印象に残る作品でした。',
            ],

            // FACTFULNESS：3件
            [
                'book_isbn' => '9784822289607',
                'user_email' => 'takahashi@example.com',
                'rating' => 5,
                'comment' => '数字やデータをもとに世界を見る重要性がよく分かりました。',
            ],
            [
                'book_isbn' => '9784822289607',
                'user_email' => 'suzuki@example.com',
                'rating' => 4,
                'comment' => '思い込みに気づかされる内容が多く、とても参考になりました。',
            ],
            [
                'book_isbn' => '9784822289607',
                'user_email' => 'tanaka@example.com',
                'rating' => 4,
                'comment' => 'データの見方について考え直す良いきっかけになりました。',
            ],

            // コンテナ物語：2件
            [
                'book_isbn' => '9784822251468',
                'user_email' => 'yamada@example.com',
                'rating' => 5,
                'comment' => 'コンテナが世界の物流を変えた過程が詳しく描かれていて面白かったです。',
            ],
            [
                'book_isbn' => '9784822251468',
                'user_email' => 'sato@example.com',
                'rating' => 4,
                'comment' => '普段意識しない物流の歴史を知ることができ、興味深く読めました。',
            ],
        ];

        collect($reviews)->each(function (array $reviewData) use ($users, $books): void {
            Review::create([
                'user_id' => $users->get($reviewData['user_email'])->id,
                'book_id' => $books->get($reviewData['book_isbn'])->id,
                'rating' => $reviewData['rating'],
                'comment' => $reviewData['comment'],
            ]);
        });
    }
}
