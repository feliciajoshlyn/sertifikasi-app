<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Garage;
use App\Models\Rental;
use App\Models\Vehicle;
use App\Services\RentalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Handles Penyewa garage rental bookings/history (including inline new vehicle registration)
 * and Admin rental extensions, delegating all pricing and overlap logic to RentalService.
 */
class RentalController extends Controller
{
    /**
     * Shared upload validation constraints for booking and extension payment proofs.
     *
     * @var list<string>
     */
    private const PAYMENT_PROOF_RULES = ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'];

    /**
     * Inject RentalService for booking creation and extension operations.
     *
     * @param RentalService $rentalService Domain service for rental workflows.
     */
    public function __construct(private readonly RentalService $rentalService)
    {
    }

    /**
     * Display the authenticated renter's rental history and extension details.
     *
     * @return View Renter rental history view.
     */
    public function index(): View
    {
        $rentals = Rental::query()
            ->with(['vehicle', 'garage', 'extensions'])
            ->where('user_id', Auth::id())
            ->orderByDesc('id')
            ->get();

        return view('penyewa.rentals.index', ['rentals' => $rentals]);
    }

    /**
     * Display the new garage rental booking form with eligible unparked vehicles and available garages.
     *
     * @return View New rental booking form view.
     */
    public function create(): View
    {
        $today = now()->toDateString();

        $totalVehiclesCount = Vehicle::query()
            ->where('user_id', Auth::id())
            ->count();

        $vehicles = Vehicle::query()
            ->where('user_id', Auth::id())
            ->whereDoesntHave('rentals', function ($query) use ($today): void {
                $query->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today);
            })
            ->orderBy('brand_model')
            ->get();

        $availableGarages = Garage::query()
            ->where('status', 'available')
            ->whereDoesntHave('rentals', function ($query) use ($today): void {
                $query->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today);
            })
            ->orderBy('code')
            ->get();

        return view('penyewa.rentals.create', [
            'vehicles' => $vehicles,
            'totalVehiclesCount' => $totalVehiclesCount,
            'availableGarages' => $availableGarages,
        ]);
    }

    /**
     * Validate booking input (plus optional inline new vehicle fields), store payment proof,
     * and delegate atomic creation to RentalService.
     *
     * @param Request $request HTTP request containing booking parameters, optional new vehicle, and proof file.
     *
     * @return RedirectResponse Redirect to rental history or back with validation/domain errors.
     */
    public function store(Request $request): RedirectResponse
    {
        $isNewVehicle = $request->input('vehicle_mode') === 'new';
        $newVehicleType = (string) $request->input('new_vehicle_type', '');

        $rawPlate = $request->input('new_vehicle_plate');
        $normalizedPlate = $isNewVehicle && $newVehicleType !== 'sepeda' && is_string($rawPlate) && trim($rawPlate) !== ''
            ? strtoupper(trim($rawPlate))
            : null;
        $request->merge(['new_vehicle_plate' => $normalizedPlate]);

        $validated = $request->validate([
            'vehicle_mode' => ['required', 'in:existing,new'],
            'vehicle_id' => ['required_if:vehicle_mode,existing', 'nullable', 'integer', 'exists:vehicles,id'],
            'new_vehicle_type' => ['required_if:vehicle_mode,new', 'nullable', 'in:sepeda,sepeda_motor,mobil,mobil_listrik'],
            'new_vehicle_plate' => [
                Rule::requiredIf(static fn (): bool => $isNewVehicle && $newVehicleType !== 'sepeda'),
                'nullable',
                'string',
                'max:20',
                'unique:vehicles,plate_number',
            ],
            'new_vehicle_brand' => ['required_if:vehicle_mode,new', 'nullable', 'string', 'max:100'],
            'new_vehicle_battery' => [
                Rule::requiredIf(static fn (): bool => $isNewVehicle && $newVehicleType === 'mobil_listrik'),
                'nullable',
                'numeric',
                'gt:0',
            ],
            'garage_id' => ['required', 'integer', 'exists:garages,id'],
            'rental_type' => ['required', 'in:harian,bulanan'],
            'start_date' => ['required', 'date'],
            'duration_days' => ['required_if:rental_type,harian', 'nullable', 'integer', 'min:1'],
            'remaining_battery_kwh' => ['nullable', 'numeric', 'min:0'],
            'payment_proof' => self::PAYMENT_PROOF_RULES,
        ], [
            'new_vehicle_plate.required' => 'Nomor plat wajib diisi untuk Sepeda Motor, Mobil, dan Mobil Listrik.',
            'new_vehicle_plate.unique' => 'Nomor plat ini sudah terdaftar pada kendaraan lain.',
            'new_vehicle_battery.required' => 'Kapasitas baterai (kWh) wajib diisi untuk Mobil Listrik.',
        ]);

        $proofPath = (string) $request->file('payment_proof')->store('proofs', 'public');

        $payload = [
            'user_id' => (int) Auth::id(),
            'garage_id' => (int) $validated['garage_id'],
            'rental_type' => $validated['rental_type'],
            'start_date' => $validated['start_date'],
            'duration_days' => $validated['rental_type'] === 'harian' ? (int) $validated['duration_days'] : null,
            'remaining_battery_kwh' => $validated['remaining_battery_kwh'] ?? null,
            'payment_proof_path' => $proofPath,
        ];

        if ($validated['vehicle_mode'] === 'new') {
            $payload['new_vehicle'] = [
                'type' => (string) $validated['new_vehicle_type'],
                'plate_number' => $validated['new_vehicle_plate'],
                'brand_model' => (string) $validated['new_vehicle_brand'],
                'battery_capacity_kwh' => isset($validated['new_vehicle_battery'])
                    ? (float) $validated['new_vehicle_battery']
                    : null,
            ];
        } else {
            $payload['vehicle_id'] = (int) $validated['vehicle_id'];
        }

        try {
            $this->rentalService->createRental($payload);
        } catch (InvalidArgumentException $exception) {
            // Remove the uploaded proof file if domain validation rejects the booking to avoid orphaned storage files.
            Storage::disk('public')->delete($proofPath);

            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('penyewa.rentals.index')->with('success', 'Garage rental booked successfully.');
    }

    /**
     * Display the Admin rental extension management page with active rentals.
     *
     * @return View Admin extension view.
     */
    public function extendForm(): View
    {
        $activeRentals = Rental::query()
            ->with(['user', 'vehicle', 'garage', 'extensions'])
            ->orderByDesc('end_date')
            ->get();

        return view('admin.rentals.extend', ['activeRentals' => $activeRentals]);
    }

    /**
     * Validate extension inputs, upload payment proof, and delegate to RentalService::extendRental().
     *
     * @param Request $request HTTP request containing new_end_date and payment_proof.
     * @param Rental  $rental  Target rental model to extend.
     *
     * @return RedirectResponse Redirect back with success or domain error message.
     */
    public function extend(Request $request, Rental $rental): RedirectResponse
    {
        $validated = $request->validate([
            'new_end_date' => ['required', 'date'],
            'payment_proof' => self::PAYMENT_PROOF_RULES,
        ]);

        $proofPath = (string) $request->file('payment_proof')->store('proofs', 'public');

        try {
            $this->rentalService->extendRental($rental->id, $validated['new_end_date'], $proofPath);
        } catch (InvalidArgumentException $exception) {
            // Remove the uploaded proof file if domain validation rejects the extension to avoid orphaned storage files.
            Storage::disk('public')->delete($proofPath);

            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.rentals.extend.form')->with('success', 'Rental extended successfully.');
    }
}
