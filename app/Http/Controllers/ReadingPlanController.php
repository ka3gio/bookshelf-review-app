<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\IndexReadingPlanRequest;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexReadingPlanRequest $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $currentStatus = $request->integer('status');

        $readingPlans = $user
            ->readingPlans()
            ->with('book')
            ->when(
                $currentStatus,
                fn ($query) => $query->where('status', $currentStatus)
            )
            ->get();

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $books = Book::all();

        return view('reading-plans.create', compact('books'));
    }

    public function store(StoreReadingPlanRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->readingPlans()->create($request->validated());

        return redirect()->route('reading-plans.index')->with('success', '読書計画を作成しました');
    }

    public function edit(string $id): View
    {
        $readingPlan = ReadingPlan::findOrFail($id);
        $this->authorize('update', $readingPlan);

        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReadingPlanRequest $request, string $id): RedirectResponse
    {
        $readingPlan = ReadingPlan::findOrFail($id);
        $this->authorize('update', $readingPlan);
        $validated = $request->validated();

        if ($readingPlan->status === ReadingPlanStatus::Expired) {
            $validated['status'] = ReadingPlanStatus::InProgress;
            $validated['completed_at'] = null;
        }

        $readingPlan->update($validated);

        return redirect()->route('reading-plans.index')->with('success', '読書計画を更新しました');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): RedirectResponse
    {
        $readingPlan = ReadingPlan::findOrFail($id);
        $this->authorize('delete', $readingPlan);
        $readingPlan->delete();

        return redirect()->route('reading-plans.index')->with('success', '読書計画を削除しました');
    }

    public function inprogress(string $id): RedirectResponse
    {
        $readingPlan = ReadingPlan::findOrFail($id);
        $this->authorize('inprogress', $readingPlan);

        $readingPlan->update([
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        return redirect()->route('reading-plans.index')->with('success', "『{$readingPlan->book->title}』を進行中にしました");
    }

    public function complete(string $id): RedirectResponse
    {
        $readingPlan = ReadingPlan::findOrFail($id);
        $this->authorize('complete', $readingPlan);

        $readingPlan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => today(),
        ]);

        return redirect()->route('reading-plans.index')->with('success', "『{$readingPlan->book->title}』を読了しました");
    }
}
