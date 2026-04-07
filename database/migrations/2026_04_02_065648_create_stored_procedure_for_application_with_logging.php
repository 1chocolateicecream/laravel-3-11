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
        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE CreateApplication(
                IN p_user_id INT,
                IN p_internship_id INT,
                IN p_company_id INT,
                IN p_motivation_letter TEXT,
                OUT p_status INT,
                OUT p_message VARCHAR(255),
                OUT p_application_id INT
            )
            BEGIN
                DECLARE v_group_id INT DEFAULT NULL;
                DECLARE v_user_role VARCHAR(50) DEFAULT NULL;
                DECLARE v_is_allowed INT DEFAULT 0;
                DECLARE v_user_exists INT DEFAULT 0;
                DECLARE v_internship_exists INT DEFAULT 0;

                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    SET p_status = 500;
                    SET p_message = 'Database error occurred';
                    SET p_application_id = NULL;
                    
                    INSERT INTO activity_logs (action, entity_type, entity_id, user_id, description, metadata, created_at, updated_at)
                    VALUES (
                        'application_failed',
                        'application',
                        NULL,
                        NULL,
                        'Kļūda izveidojot pieteikumu: Database error occurred',
                        JSON_OBJECT(
                            'error', 'Database error occurred',
                            'attempted_user_id', p_user_id,
                            'internship_id', p_internship_id,
                            'company_id', p_company_id
                        ),
                        CURRENT_TIMESTAMP(),
                        CURRENT_TIMESTAMP()
                    );
                END;

                START TRANSACTION;

                -- a) Pārbauda: vai eksistē lietotājs
                SELECT COUNT(*) INTO v_user_exists
                FROM users
                WHERE id = p_user_id;

                IF v_user_exists > 0 THEN
                    SELECT role, group_id
                    INTO v_user_role, v_group_id
                    FROM users
                    WHERE id = p_user_id;
                END IF;

                IF v_user_exists = 0 THEN
                    SET p_status = 404;
                    SET p_message = 'User not found';
                    SET p_application_id = NULL;
                    
                    INSERT INTO activity_logs (action, entity_type, entity_id, user_id, description, metadata, created_at, updated_at)
                    VALUES (
                        'application_failed',
                        'application',
                        NULL,
                        NULL,
                        CONCAT('Kļūda izveidojot pieteikumu: User not found (ID: ', p_user_id, ')'),
                        JSON_OBJECT(
                            'error', 'User not found',
                            'attempted_user_id', p_user_id,
                            'internship_id', p_internship_id,
                            'company_id', p_company_id
                        ),
                        CURRENT_TIMESTAMP(),
                        CURRENT_TIMESTAMP()
                    );
                    
                    COMMIT;
                ELSE
                    -- b) Pārbauda: vai eksistē prakse
                    SELECT COUNT(*) INTO v_internship_exists
                    FROM internships
                    WHERE id = p_internship_id;

                    IF v_internship_exists = 0 THEN
                        SET p_status = 404;
                        SET p_message = 'Internship not found';
                        SET p_application_id = NULL;
                        
                        INSERT INTO activity_logs (action, entity_type, entity_id, user_id, description, metadata, created_at, updated_at)
                        VALUES (
                            'application_failed',
                            'application',
                            NULL,
                            p_user_id,
                            CONCAT('Kļūda izveidojot pieteikumu: Internship not found (ID: ', p_internship_id, ')'),
                            JSON_OBJECT(
                                'error', 'Internship not found',
                                'user_id', p_user_id,
                                'attempted_internship_id', p_internship_id,
                                'company_id', p_company_id
                            ),
                            CURRENT_TIMESTAMP(),
                            CURRENT_TIMESTAMP()
                        );
                        
                        COMMIT;
                    ELSE
                        -- c) Pārbauda: vai lietotājs drīkst pieteikties
                        SELECT EXISTS(
                            SELECT 1 FROM group_internships
                            WHERE group_id = v_group_id
                            AND internship_id = p_internship_id
                        ) INTO v_is_allowed;

                        IF v_user_role != 'student' OR v_is_allowed = 0 THEN
                            SET p_status = 403;
                            SET p_message = 'User is not allowed to apply for this internship';
                            SET p_application_id = NULL;
                            
                            INSERT INTO activity_logs (action, entity_type, entity_id, user_id, description, metadata, created_at, updated_at)
                            VALUES (
                                'application_failed',
                                'application',
                                NULL,
                                p_user_id,
                                CONCAT('Kļūda izveidojot pieteikumu: User is not allowed (role: ', v_user_role, ', allowed: ', v_is_allowed, ')'),
                                JSON_OBJECT(
                                    'error', 'User not allowed',
                                    'user_id', p_user_id,
                                    'user_role', v_user_role,
                                    'is_allowed', v_is_allowed,
                                    'group_id', v_group_id,
                                    'internship_id', p_internship_id,
                                    'company_id', p_company_id
                                ),
                                CURRENT_TIMESTAMP(),
                                CURRENT_TIMESTAMP()
                            );
                            
                            COMMIT;
                        ELSE
                            -- visas pārbaudes izturētas - izveido pieteikumu
                            INSERT INTO applications (
                                user_id, group_id, internship_id, company_id,
                                motivation_letter, status, created_at, updated_at
                            ) VALUES (
                                p_user_id, v_group_id, p_internship_id, p_company_id,
                                p_motivation_letter, 'pending', CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP()
                            );

                            SET p_application_id = LAST_INSERT_ID();
                            SET p_status = 201;
                            SET p_message = 'Application created successfully';
                            COMMIT;
                        END IF;
                    END IF;
                END IF;
            END;
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP PROCEDURE IF EXISTS CreateApplication');
    }
};
