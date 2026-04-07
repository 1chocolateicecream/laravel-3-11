<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Allow trigger creation without SUPER privilege
        DB::unprepared('SET GLOBAL log_bin_trust_function_creators = 1');

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER log_application_created
            AFTER INSERT ON applications
            FOR EACH ROW
            BEGIN
                INSERT INTO activity_logs (action, entity_type, entity_id, user_id, description, metadata, created_at, updated_at)
                VALUES (
                    'application_created',
                    'application',
                    NEW.id,
                    NEW.user_id,
                    CONCAT('Prakses pieteikums izveidots veiksmīgi. Internship ID: ', NEW.internship_id, ', Company ID: ', IFNULL(NEW.company_id, 'N/A')),
                    JSON_OBJECT(
                        'internship_id', NEW.internship_id,
                        'company_id', NEW.company_id,
                        'group_id', NEW.group_id,
                        'status', NEW.status
                    ),
                    NOW(),
                    NOW()
                );
            END;
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS log_application_created');
    }
};
