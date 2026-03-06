<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ Ensure sequence exists first (for PostgreSQL)
        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_class WHERE relname = 'cases_id_seq') THEN
                    CREATE SEQUENCE cases_id_seq START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
                END IF;
            END
            $$;
        ");

        // ✅ Create table
        Schema::create('cases', function (Blueprint $table) {
            $table->integer('id')->primary()->default(DB::raw("nextval('cases_id_seq'::regclass)"));
            $table->string('case_number', 256)->unique();
            $table->string('case_type', 256)->nullable();
            $table->string('case_status', 256)->nullable();
            $table->text('case_description')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Foreign key references
            $table->integer('created_by');
            $table->timestamp('created_date')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->integer('modified_by');
            $table->timestamp('last_modified_date')->default(DB::raw('CURRENT_TIMESTAMP'));

            // Index
            $table->index('id');
        });

        // ✅ Add foreign key constraints (PostgreSQL schema "core")
        Schema::table('cases', function (Blueprint $table) {
            $table->foreign('created_by')
                ->references('id')
                ->on('core.users')
                ->onUpdate('no action')
                ->onDelete('no action');

            $table->foreign('modified_by')
                ->references('id')
                ->on('core.users')
                ->onUpdate('no action')
                ->onDelete('no action');
        });

        // ✅ Link the sequence to the id column
        DB::statement("ALTER SEQUENCE cases_id_seq OWNED BY cases.id");
    }

    public function down(): void
    {
        Schema::dropIfExists('cases');
        DB::statement('DROP SEQUENCE IF EXISTS cases_id_seq CASCADE');
    }
};
