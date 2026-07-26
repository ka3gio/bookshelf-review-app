<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReadingPlan;
use App\Models\Book;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;

class ReadingPlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $status)
    {
        $user = auth()->user();

        $currentStatus = $status->integer('status');

        $readingPlans = $user
            ->readingPlans()
            ->with('book')
            ->when(
                $currentStatus,
                fn($query) => $query->where('status', $currentStatus)
            )
            ->get();


        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $books = Book::all();
        return view('reading-plans.create', compact('books'));
    }

    public function store(StoreReadingPlanRequest $request)
    {
        $validated = $request->validated();
        $readingPlan = auth()->user()->readingPlans()->create($validated);
        return redirect()->route('reading-plans.index')->with('success', '読書計画を作成しました');
    }

    public function edit(string $id)
    {
        $readingPlan = ReadingPlan::findOrFail($id);
        $this->authorize('update', $readingPlan);

        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReadingPlanRequest $request, string $id)
    {
        $readingPlan = ReadingPlan::findOrFail($id);
        $this->authorize('update', $readingPlan);
        $validated = $request->validated();
        $readingPlan->update($validated);

        return redirect()->route('reading-plans.index')->with('success', '読書計画を更新しました');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $readingPlan = ReadingPlan::findOrFail($id);
        $this->authorize('delete', $readingPlan);
        $readingPlan->delete();

        return redirect()->route('reading-plans.index')->with('success', '読書計画を削除しました');
    }

    public function inprogress(string $id)
    {
        $readingPlan = ReadingPlan::findOrFail($id);
        $this->authorize('inprogress', $readingPlan);

        $readingPlan->update([
            'status' => 2,
            'completed_at' => null,
        ]);

        return redirect()->route('reading-plans.index')->with('success', "『{$readingPlan->book->title}』を進行中にしました");
    }

    public function complete(string $id)
    {
        $readingPlan = ReadingPlan::findOrFail($id);
        $this->authorize('complete', $readingPlan);

        $readingPlan->update([
            'status' => 3,
            'completed_at' => today(),
        ]);

        return redirect()->route('reading-plans.index')->with('success', "『{$readingPlan->book->title}』を読了しました");
    }
}
