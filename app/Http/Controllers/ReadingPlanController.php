<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    /**
     * ログインユーザーの読書計画一覧を表示する。
     */
    public function index(Request $request): View
    {
        $currentStatus = $request->query('status');

        if (! is_string($currentStatus)) {
            $currentStatus = '';
        }

        $status = ReadingPlanStatus::tryFrom($currentStatus);

        $query = $request->user()
            ->readingPlans()
            ->with('book');

        if ($status !== null) {
            $query->where('status', $status->value);
        } else {
            $currentStatus = '';
        }

        $readingPlans = $query
            ->orderBy('target_date')
            ->get();

        return view('reading-plans.index', compact(
            'readingPlans',
            'currentStatus'
        ));
    }

    /**
     * 読書計画作成画面を表示する。
     */
    public function create(): View
    {
        $books = Book::all();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * 読書計画を登録する。
     */
    public function store(StoreReadingPlanRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $request->user()->readingPlans()->create([
            'book_id' => $validated['book_id'],
            'target_date' => $validated['target_date'],
            'status' => ReadingPlanStatus::InProgress,
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を登録しました。');
    }

    /**
     * 読書計画編集画面を表示する。
     */
    public function edit(ReadingPlan $plan): View
    {
        $this->authorize('update', $plan);

        $plan->load('book');

        $readingPlan = $plan;

        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * 読書計画を更新する。
     */
    public function update(UpdateReadingPlanRequest $request, ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('update', $plan);

        $validated = $request->validated();

        if (
            $plan->status === ReadingPlanStatus::Expired
            && Carbon::parse($validated['target_date'])->greaterThanOrEqualTo(today())
        ) {
            $validated['status'] = ReadingPlanStatus::InProgress;
        }

        $plan->update($validated);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を更新しました。');
    }

    /**
     * 読書計画を削除する。
     */
    public function destroy(ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('delete', $plan);

        $plan->delete();

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を削除しました。');
    }

    /**
     * 読書計画を読了済みにする。
     */
    public function complete(ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('complete', $plan);

        $plan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を読了しました。');
    }
}
