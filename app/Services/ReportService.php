<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Generates Admin financial and operational rental reports using explicit SQL JOIN queries
 * across rentals, users, garages, vehicles, and rental_extensions.
 */
class ReportService
{
    /**
     * Retrieve monthly rental counts and revenue totals for a given calendar year.
     *
     * @param int $year Four-digit calendar year (e.g., 2026).
     *
     * @return array<int, object> Monthly aggregated report rows ordered by month ascending.
     */
    public function getMonthlyReport(int $year): array
    {
        $monthExpression = $this->monthExpression();

        $query = $this->baseJoinedQuery()
            ->whereYear('rentals.start_date', $year)
            ->selectRaw("{$monthExpression} AS month");

        return $this->applyRevenueBreakdownColumns($query)
            ->groupByRaw($monthExpression)
            ->orderByRaw("{$monthExpression} ASC")
            ->get()
            ->all();
    }

    /**
     * Retrieve daily rental counts and revenue totals for a specific year and month.
     *
     * @param int $year  Four-digit calendar year.
     * @param int $month Month number (1..12).
     *
     * @return array<int, object> Daily aggregated report rows ordered by date ascending.
     *
     * @throws InvalidArgumentException When month is outside 1..12.
     */
    public function getDailyReport(int $year, int $month): array
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException("Month must be between 1 and 12; got {$month}.");
        }

        $query = $this->baseJoinedQuery()
            ->whereYear('rentals.start_date', $year)
            ->whereMonth('rentals.start_date', $month)
            ->selectRaw('DATE(rentals.start_date) AS rental_date');

        return $this->applyRevenueBreakdownColumns($query)
            ->groupByRaw('DATE(rentals.start_date)')
            ->orderByRaw('DATE(rentals.start_date) ASC')
            ->get()
            ->all();
    }

    /**
     * Retrieve rental counts and revenue totals grouped by garage type.
     *
     * @return array<int, object> Aggregated report rows per garage type.
     */
    public function getReportByGarageType(): array
    {
        return $this->baseJoinedQuery()
            ->selectRaw('garages.type AS garage_type')
            ->selectRaw('COUNT(rentals.id) AS total_rentals')
            ->selectRaw('COALESCE(SUM(rentals.total_cost + COALESCE(ext.total_extension_cost, 0)), 0) AS total_revenue')
            ->groupBy('garages.type')
            ->orderBy('garages.type')
            ->get()
            ->all();
    }

    /**
     * Retrieve rental counts and total spending grouped by renter account.
     *
     * @return array<int, object> Aggregated report rows per renter ordered by highest revenue.
     */
    public function getReportByRenter(): array
    {
        return $this->baseJoinedQuery()
            ->selectRaw('users.id AS user_id')
            ->selectRaw('users.name AS renter_name')
            ->selectRaw('users.email AS renter_email')
            ->selectRaw('COUNT(rentals.id) AS total_rentals')
            ->selectRaw('COALESCE(SUM(rentals.total_cost + COALESCE(ext.total_extension_cost, 0)), 0) AS total_revenue')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total_revenue')
            ->get()
            ->all();
    }

    /**
     * Append shared rental count and revenue breakdown aggregate columns for period reports.
     *
     * @param Builder $query Joined rental query builder.
     *
     * @return Builder Query builder with total_rentals and revenue breakdown expressions attached.
     */
    private function applyRevenueBreakdownColumns(Builder $query): Builder
    {
        return $query
            ->selectRaw('COUNT(rentals.id) AS total_rentals')
            ->selectRaw('COALESCE(SUM(rentals.base_rental_cost), 0) AS base_revenue')
            ->selectRaw('COALESCE(SUM(rentals.charging_cost), 0) AS charging_revenue')
            ->selectRaw('COALESCE(SUM(COALESCE(ext.total_extension_cost, 0)), 0) AS extension_revenue')
            ->selectRaw('COALESCE(SUM(rentals.total_cost + COALESCE(ext.total_extension_cost, 0)), 0) AS total_revenue');
    }

    /**
     * Build the base query joining rentals with users, garages, vehicles,
     * and pre-aggregated rental_extensions.
     *
     * @return Builder Query builder configured with explicit SQL JOIN clauses.
     */
    private function baseJoinedQuery(): Builder
    {
        // Pre-aggregate extensions in a subquery so multiple extensions per rental never duplicate parent rental totals.
        $extensionsSummary = DB::table('rental_extensions')
            ->selectRaw('rental_id, SUM(extension_cost) AS total_extension_cost')
            ->groupBy('rental_id');

        return DB::table('rentals')
            ->join('users', 'users.id', '=', 'rentals.user_id')
            ->join('garages', 'garages.id', '=', 'rentals.garage_id')
            // rentals.vehicle_id uses ON DELETE RESTRICT so INNER JOIN always preserves historical rental reports.
            ->join('vehicles', 'vehicles.id', '=', 'rentals.vehicle_id')
            ->leftJoinSub($extensionsSummary, 'ext', 'ext.rental_id', '=', 'rentals.id');
    }

    /**
     * Build the driver-appropriate SQL expression extracting month number (1..12) from rentals.start_date
     * so monthly reports run identically on MySQL in production and SQLite in-memory during PHPUnit tests.
     *
     * @return string SQL month extraction expression.
     */
    private function monthExpression(): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return "CAST(strftime('%m', rentals.start_date) AS INTEGER)";
        }

        return 'MONTH(rentals.start_date)';
    }
}
