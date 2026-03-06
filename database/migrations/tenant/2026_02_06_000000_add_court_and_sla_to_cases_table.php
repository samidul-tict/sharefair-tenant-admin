<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            if (!Schema::hasColumn('cases', 'court_name')) {
                $table->string('court_name', 256)->nullable()->after('case_description');
            }
            if (!Schema::hasColumn('cases', 'sla_deadline')) {
                $table->date('sla_deadline')->nullable()->after('court_name');
            }
            if (!Schema::hasColumn('cases', 'asset_sla_in_days')) {
                $table->integer('asset_sla_in_days')->nullable()->after('sla_deadline');
            }
            if (!Schema::hasColumn('cases', 'max_number_of_arbitation_per_user')) {
                $table->integer('max_number_of_arbitation_per_user')->nullable()->after('asset_sla_in_days');
            }
        });
    }

    public function down(): void
    {
        $columns = ['court_name', 'sla_deadline', 'asset_sla_in_days', 'max_number_of_arbitation_per_user'];
        $drop = array_filter($columns, fn ($col) => Schema::hasColumn('cases', $col));
        if (!empty($drop)) {
            Schema::table('cases', fn (Blueprint $table) => $table->dropColumn($drop));
        }
    }
};
