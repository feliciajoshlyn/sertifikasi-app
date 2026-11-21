<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Serves Admin financial and operational rental reports across four report dimensions.
 */
class ReportController extends Controller
{
    /**
     * Supported report dimension keys.
     *
     * @var list<string>
     */
    private const ALLOWED_FILTERS = ['monthly', 'daily', 'garage_type', 'renter'];

    /**
     * Inject ReportService for SQL JOIN-based report queries.
     *
     * @param ReportService $reportService Domain service for report generation.
     */
    public function __construct(private readonly ReportService $reportService)
    {
    }

    /**
     * Display the selected report view (monthly, daily, garage_type, or renter).
     *
     * @param Request $request HTTP request containing optional filter_type, year, and month.
     *
     * @return View Admin report index view.
     */
    public function index(Request $request): View
    {
        $requestedFilter = (string) $request->query('filter_type', 'monthly');
        $filterType = in_array($requestedFilter, self::ALLOWED_FILTERS, true) ? $requestedFilter : 'monthly';
        $year = max(2000, min(2100, (int) $request->query('year', now()->year)));
        $month = max(1, min(12, (int) $request->query('month', now()->month)));

        $reportData = match ($filterType) {
            'daily' => $this->reportService->getDailyReport($year, $month),
            'garage_type' => $this->reportService->getReportByGarageType(),
            'renter' => $this->reportService->getReportByRenter(),
            default => $this->reportService->getMonthlyReport($year),
        };

        return view('admin.reports.index', [
            'reportData' => $reportData,
            'filterType' => $filterType,
            'year' => $year,
            'month' => $month,
        ]);
    }
}
