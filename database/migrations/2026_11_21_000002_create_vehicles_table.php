<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->index()
                ->constrained('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->enum('type', ['sepeda', 'sepeda_motor', 'mobil', 'mobil_listrik'])->index();
            $table->string('plate_number', 20)->nullable()->index();
            $table->string('brand_model', 100);
            $table->decimal('battery_capacity_kwh', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Drop the vehicles table.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
