<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * tracking Admin-approved rental duration extensions and payment proofs.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('rental_extensions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rental_id')
                ->index()
                ->constrained('rentals')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->date('previous_end_date');
            $table->date('new_end_date');
            $table->unsignedInteger('added_days');
            $table->decimal('extension_cost', 12, 2);
            $table->string('payment_proof_path');
            $table->timestamps();
        });
    }

    /**
     * Drop the rental_extensions table.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_extensions');
    }
};
