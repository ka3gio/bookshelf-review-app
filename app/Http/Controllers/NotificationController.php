<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * ログインユーザーの通知一覧を表示する
     *
     * @return View 通知一覧画面
     */
    public function index(): View
    {
        $notifications = auth()->user()->notifications()->get();

        return view('notifications.index', compact('notifications'));
    }

    /**
     * 指定した通知を既読にする
     *
     * @param  Request  $request  リクエスト
     * @param  string  $id  通知ID
     * @return RedirectResponse 直前の画面へのリダイレクト
     */
    public function read(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return back();
    }
}
