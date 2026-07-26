<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        $books = [
            [
                'title' => '吾輩は猫である',
                'author' => '夏目漱石',
                'isbn' => '9784101010014',
                'published_date' => '1905-01-01',
                'description' => "名前のない一匹の猫が、明治の知識人たちの暮らしを少し距離を置いて観察します。\n猫の皮肉な語り口を通して、人間の見栄や滑稽さが浮かび上がる夏目漱石の代表作です。",
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
                'genres' => ['小説'],
            ],
            [
                'title' => '人を動かす',
                'author' => 'D・カーネギー',
                'isbn' => '9784422100524',
                'published_date' => '1936-10-01',
                'description' => "人との関係を築くための基本原則を、具体的な事例とともに紹介します。\n相手の立場を理解し、信頼を得るための考え方を体系的に学べるロングセラーです。",
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=2',
                'genres' => ['ビジネス', '自己啓発'],
            ],
            [
                'title' => 'リーダブルコード',
                'author' => 'Dustin Boswell',
                'isbn' => '9784873115658',
                'published_date' => '2012-06-23',
                'description' => "変数名、コメント、制御フローなどを題材に、読みやすいコードの書き方を解説します。\nチームで保守しやすい実装を目指す開発者に役立つ、実践的な技術書です。",
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=3',
                'genres' => ['技術書'],
            ],
            [
                'title' => '7つの習慣',
                'author' => 'スティーブン・R・コヴィー',
                'isbn' => '9784863940246',
                'published_date' => '2013-08-30',
                'description' => "主体性から始めて、目標設定、対人関係、自己研鑽へと進む七つの習慣を説明します。\n一時的なテクニックではなく、長期的に成果を出すための原則を学べる一冊です。",
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=4',
                'genres' => ['ビジネス', '自己啓発'],
            ],
            [
                'title' => '坊っちゃん',
                'author' => '夏目漱石',
                'isbn' => '9784101010021',
                'published_date' => '1906-04-01',
                'description' => "正義感が強く曲がったことが嫌いな青年教師が、四国の中学校へ赴任します。\n個性豊かな教師や生徒との衝突を、歯切れのよい語りで描いた痛快な物語です。",
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=5',
                'genres' => ['小説'],
            ],
            [
                'title' => 'サピエンス全史',
                'author' => 'ユヴァル・ノア・ハラリ',
                'isbn' => '9784309226712',
                'published_date' => '2016-09-08',
                'description' => "ホモ・サピエンスが虚構を共有する力によって繁栄した過程をたどります。\n認知革命、農業革命、科学革命を切り口に、人類史を大きな視野で読み解く一冊です。",
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=6',
                'genres' => ['歴史', '科学'],
            ],
            [
                'title' => 'Clean Code',
                'author' => 'Robert C. Martin',
                'isbn' => '9784048930598',
                'published_date' => '2017-12-18',
                'description' => "関数、命名、エラー処理、テストなどを通じて、変更しやすいコードの条件を考えます。\n悪いコードを改善するリファクタリングの視点を学べる、開発者向けの定番書です。",
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=7',
                'genres' => ['技術書'],
            ],
            [
                'title' => '嫌われる勇気',
                'author' => '岸見一郎・古賀史健',
                'isbn' => '9784478025819',
                'published_date' => '2013-12-13',
                'description' => "哲人と青年の対話を通して、アドラー心理学の考え方をわかりやすく紹介します。\n他者の期待に縛られず、自分の課題に向き合うための視点を得られる一冊です。",
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=8',
                'genres' => ['自己啓発'],
            ],
            [
                'title' => '火花',
                'author' => '又吉直樹',
                'isbn' => '9784163902302',
                'published_date' => '2015-03-11',
                'description' => "売れない芸人・徳永と先輩芸人・神谷の交流を軸に、漫才への情熱を描きます。\n夢を追う喜びと現実の厳しさの間で揺れる若者たちの姿が印象的な小説です。",
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=9',
                'genres' => ['小説'],
            ],
            [
                'title' => 'FACTFULNESS',
                'author' => 'ハンス・ロスリング',
                'isbn' => '9784822289607',
                'published_date' => '2019-01-11',
                'description' => "世界を悲観的に見誤る原因となる十の思い込みを、豊富な統計データで検証します。\n直感だけに頼らず、事実に基づいて世界を見る習慣を身につけるための一冊です。",
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=10',
                'genres' => ['ビジネス', '科学'],
            ],
            [
                'title' => 'コンテナ物語',
                'author' => 'マルク・レビンソン',
                'isbn' => '9784822251468',
                'published_date' => '2007-01-18',
                'description' => "規格化された海上コンテナが、荷役作業と国際物流をどのように変えたのかを追います。\n港湾、企業、労働者の変化を通して、世界経済の仕組みを描くノンフィクションです。",
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=11',
                'genres' => ['ビジネス', '歴史'],
            ],
        ];

        foreach ($books as $bookData) {
            $book = Book::firstOrCreate(
                ['isbn' => $bookData['isbn']],
                [
                    'user_id' => $users->random()->id,
                    'title' => $bookData['title'],
                    'author' => $bookData['author'],
                    'published_date' => $bookData['published_date'],
                    'description' => $bookData['description'],
                    'image_url' => $bookData['image_url'],
                ]
            );

            $genreIds = Genre::whereIn('name', $bookData['genres'])->pluck('id')->all();

            $book->genres()->sync($genreIds);
        }
    }
}
