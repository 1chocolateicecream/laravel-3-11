<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
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
                END;

                START TRANSACTION;

                -- a) Проверка: существует ли пользователь
                SELECT COUNT(*), role, group_id
                INTO v_user_exists, v_user_role, v_group_id
                FROM users
                WHERE user_id = p_user_id;

                IF v_user_exists = 0 THEN
                    SET p_status = 404;
                    SET p_message = 'User not found';
                    SET p_application_id = NULL;
                    COMMIT;
                ELSE
                    -- b) Проверка: существует ли практика
                    SELECT COUNT(*) INTO v_internship_exists
                    FROM internships
                    WHERE internship_id = p_internship_id;

                    IF v_internship_exists = 0 THEN
                        SET p_status = 404;
                        SET p_message = 'Internship not found';
                        SET p_application_id = NULL;
                        COMMIT;
                    ELSE
                        -- c) Проверка: имеет ли право пользователь подавать заявку
                        SELECT EXISTS(
                            SELECT 1 FROM group_internships
                            WHERE group_id = v_group_id
                            AND internship_id = p_internship_id
                        ) INTO v_is_allowed;

                        IF v_user_role != 'student' OR v_is_allowed = 0 THEN
                            SET p_status = 403;
                            SET p_message = 'User is not allowed to apply for this internship';
                            SET p_application_id = NULL;
                            COMMIT;
                        ELSE
                            -- все проверки пройдены - создаём заявку
                            INSERT INTO applications (
                                user_id, group_id, internship_id, company_id,
                                motivation_letter, status, created_at, updated_at
                            ) VALUES (
                                p_user_id, v_group_id, p_internship_id, p_company_id,
                                p_motivation_letter, 'pending', NOW(), NOW()
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

    public function down(): void
    {
        DB::statement('DROP PROCEDURE IF EXISTS CreateApplication');
    }
};