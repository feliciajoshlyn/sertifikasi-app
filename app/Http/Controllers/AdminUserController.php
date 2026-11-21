<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Allows Admin users to review pending Penyewa registrations and approve or reject them.
 */
class AdminUserController extends Controller
{
    /**
     * Display pending and recently reviewed Penyewa accounts.
     *
     * @return View Admin user verification view.
     */
    public function index(): View
    {
        $renters = User::query()
            ->where('role', 'penyewa')
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
            ->orderByDesc('id')
            ->get();

        return view('admin.users.index', ['renters' => $renters]);
    }

    /**
     * Update a Penyewa account's verification status to approved or rejected.
     *
     * @param Request $request HTTP request containing the target status.
     * @param User    $user    Target Penyewa user model.
     *
     * @return RedirectResponse Redirect back with confirmation message.
     */
    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        if ($user->role !== 'penyewa') {
            return back()->with('error', 'Only Penyewa accounts can be verified here.');
        }

        if ($user->isApproved()) {
            return back()->with('error', 'Approved renter accounts cannot be rejected or modified.');
        }

        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
        ]);

        $user->update(['status' => $validated['status']]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Renter {$user->name} marked as {$validated['status']}.");
    }
}
