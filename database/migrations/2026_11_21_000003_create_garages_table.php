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
        Schema::create('garages', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 10)->unique();
            $table->enum('type', ['sepeda', 'mobil', 'mobil_listrik'])->index();
            $table->decimal('daily_rate', 12, 2);
            $table->decimal('kwh_price', 10, 2)->nullable();
            $table->enum('status', ['available', 'used'])->default('available')->index();
            $table->timestamps();
        });
    }

    /**
     * Drop the garages table.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('garages');
    }
};
