<?php

use App\Models\Received;
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
        Schema::create('inventory_transaction', function (Blueprint $table) {

                $table->id();

                $table->uuid('uuid')->unique();

                $table->foreignUuid('blood_bank_uuid')
                    ->constrained('blood_bank', 'uuid')
                    ->cascadeOnDelete();


                 $table->foreignUuid('blood_collection_uuid')
                    ->constrained('blood_collection', 'uuid')  
                    ->cascadeOnDelete();

                $table->foreignUuid('blood_component_uuid')
                    ->constrained('blood_components', 'uuid')
                    ->cascadeOnDelete();

                $table->string('transaction_type');

                $table->string('blood_group')->nullable();

                $table->string('component_type')->nullable();

                $table->string('storage_type')->nullable();

                $table->string('previous_status')->nullable();

                $table->string('stock_status')->nullable();

                $table->string('expiry_alert')->nullable();

                $table->string('reference_type')->nullable();

                $table->uuid('reference_uuid')->nullable();

                $table->foreignUuid('performed_by_uuid')
                    ->nullable()
                    ->constrained('users', 'uuid')
                    ->nullOnDelete();

                $table->text('reason')->nullable();

                $table->timestamps();
            });
        }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transaction');
    }
};
