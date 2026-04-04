<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OrderExportService;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function index()
    {
        return view('admin.export.index');
    }

    public function orders(Request $request, OrderExportService $exportService)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        [$year, $month] = explode('-', $request->month);

        $path = $exportService->export((int) $year, (int) $month);

        return response()->download($path)->deleteFileAfterSend();
    }
}
