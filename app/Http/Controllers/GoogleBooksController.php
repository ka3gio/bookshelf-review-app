<?php

namespace App\Http\Controllers;

use App\Http\Requests\GoogleBooksRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class GoogleBooksController extends Controller
{
    public function show(GoogleBooksRequest $request): JsonResponse
    {
        $isbn = $request->validated('isbn');

        try {
            $response = Http::timeout(10)->get('https://www.googleapis.com/books/v1/volumes', [
                'q' => "isbn:{$isbn}",
                'maxResults' => 1,
                'key' => config('services.google_books.key'),
            ]);
        } catch (ConnectionException) {
            return response()->json([
                'error' => '通信エラーが発生しました。',
            ], 503);
        }

        if ($response->failed()) {
            return response()->json([
                'error' => '書籍情報の取得に失敗しました。',
            ], 502);
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            return response()->json([
                'error' => '書籍情報の取得に失敗しました。',
            ], 502);
        }

        $items = data_get($payload, 'items');

        if (data_get($payload, 'totalItems') === 0 || $items === []) {
            return response()->json([
                'error' => '書籍が見つかりませんでした。',
            ], 404);
        }

        $volume = data_get($payload, 'items.0.volumeInfo');

        if (! is_array($volume)) {
            return response()->json([
                'error' => '書籍情報の取得に失敗しました。',
            ], 502);
        }

        return response()->json([
            'title' => data_get($volume, 'title'),
            'author' => implode('、', data_get($volume, 'authors', [])),
            'description' => data_get($volume, 'description'),
            'image_url' => data_get($volume, 'imageLinks.thumbnail'),
            'published_date' => data_get($volume, 'publishedDate'),
        ], 200);
    }
}
