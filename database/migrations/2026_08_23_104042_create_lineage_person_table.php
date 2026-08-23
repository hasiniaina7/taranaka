<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lineage_person', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('lineage_id')->constrained('lineages')->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['lineage_id', 'person_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lineage_person');
    }
};
