<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetsOrderReviewService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GoogleSheetsOrderReviewController extends Controller
{
    public function store(Request $request, GoogleSheetsOrderReviewService $review)
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'date_field' => ['required', Rule::in(['business_date', 'created_at', 'delivery_date'])],
        ]);

        try {
            $result = $review->syncDay($request->user(), $validated['date'], $validated['date_field']);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'Không thể đồng bộ đối soát Google Sheets: '.$exception->getMessage());
        }

        return back()
            ->with('success', 'Đã đồng bộ '.number_format($result['orders']).' đơn, '.number_format($result['details']).' dòng chi tiết (gồm '.number_format($result['deleted']).' đơn đã xóa) của ngày '.Carbon::parse($validated['date'])->format('d/m/Y').'.')
            ->with('google_sheets_url', 'https://docs.google.com/spreadsheets/d/'.config('services.google_sheets.order_spreadsheet_id').'/edit');
    }
}
