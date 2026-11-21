<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the 3NF rentals table storing rental bookings and historical cost snapshots
     * with RESTRICT foreign keys to preserve financial and audit history.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->index()
                ->constrained('users')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table->foreignId('vehicle_id')
                ->index()
                ->constrained('vehicles')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table->foreignId('garage_id')
                ->index()
                ->constrained('garages')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table->enum('rental_type', ['harian', 'bulanan'])->index();
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->unsignedInteger('duration_days');
            $table->decimal('remaining_battery_kwh', 8, 2)->nullable();
            $table->decimal('base_rental_cost', 12, 2);
            $table->decimal('charging_cost', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2);
            $table->string('payment_proof_path');
            $table->timestamps();
        });
    }

    /**
     * Drop the rentals table.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
