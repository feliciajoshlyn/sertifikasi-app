<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Garage;
use App\Services\GarageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Handles Admin garage unit listing and batch creation via GarageService.
 */
class GarageController extends Controller
{
    /**
     * Inject GarageService for sequential batch garage generation.
     *
     * @param GarageService $garageService Domain service for garage management.
     */
    public function __construct(private readonly GarageService $garageService)
    {
    }

    /**
     * Display all garage units sorted by their generated code.
     *
     * @return View Admin garage index view.
     */
    public function index(): View
    {
        $garages = Garage::query()->orderBy('code')->get();

        return view('admin.garages.index', ['garages' => $garages]);
    }

    /**
     * Validate batch creation parameters and delegate to GarageService::createBatch().
     *
     * @param Request $request HTTP request containing type, count, daily_rate, and optional kwh_price.
     *
     * @return RedirectResponse Redirect back with creation summary or error.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:sepeda,mobil,mobil_listrik'],
            'count' => ['required', 'integer', 'min:1', 'max:50'],
            'daily_rate' => ['required', 'numeric', 'gt:0'],
            'kwh_price' => ['required_if:type,mobil_listrik', 'nullable', 'numeric', 'gt:0'],
        ]);

        try {
            $created = $this->garageService->createBatch(
                $validated['type'],
                (int) $validated['count'],
                (float) $validated['daily_rate'],
                isset($validated['kwh_price']) ? (float) $validated['kwh_price'] : null
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.garages.index')
            ->with('success', count($created) . ' garage unit(s) created.');
    }
}
