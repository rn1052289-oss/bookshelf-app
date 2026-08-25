<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * マイ読書レポートを表示する。
     */
    public function index(Request $request): View
    {
        $stats = $request->user()->readingReportStats();

        return view('reports.index', compact('stats'));
    }
}
