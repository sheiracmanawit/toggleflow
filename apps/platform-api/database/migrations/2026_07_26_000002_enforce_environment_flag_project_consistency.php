<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER environment_flags_project_consistency_insert
                BEFORE INSERT ON environment_flags
                FOR EACH ROW
                WHEN NOT EXISTS (
                    SELECT 1
                    FROM environments
                    INNER JOIN feature_flags
                        ON feature_flags.project_id = environments.project_id
                    WHERE environments.id = NEW.environment_id
                        AND feature_flags.id = NEW.feature_flag_id
                )
                BEGIN
                    SELECT RAISE(ABORT, 'Environment and feature flag must belong to the same project.');
                END
                SQL);

            DB::unprepared(<<<'SQL'
                CREATE TRIGGER environment_flags_project_consistency_update
                BEFORE UPDATE OF environment_id, feature_flag_id ON environment_flags
                FOR EACH ROW
                WHEN NOT EXISTS (
                    SELECT 1
                    FROM environments
                    INNER JOIN feature_flags
                        ON feature_flags.project_id = environments.project_id
                    WHERE environments.id = NEW.environment_id
                        AND feature_flags.id = NEW.feature_flag_id
                )
                BEGIN
                    SELECT RAISE(ABORT, 'Environment and feature flag must belong to the same project.');
                END
                SQL);

            return;
        }

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER environment_flags_project_consistency_insert
            BEFORE INSERT ON environment_flags
            FOR EACH ROW
            BEGIN
                IF NOT EXISTS (
                    SELECT 1
                    FROM environments
                    INNER JOIN feature_flags
                        ON feature_flags.project_id = environments.project_id
                    WHERE environments.id = NEW.environment_id
                        AND feature_flags.id = NEW.feature_flag_id
                ) THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Environment and feature flag must belong to the same project.';
                END IF;
            END
            SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER environment_flags_project_consistency_update
            BEFORE UPDATE ON environment_flags
            FOR EACH ROW
            BEGIN
                IF NOT EXISTS (
                    SELECT 1
                    FROM environments
                    INNER JOIN feature_flags
                        ON feature_flags.project_id = environments.project_id
                    WHERE environments.id = NEW.environment_id
                        AND feature_flags.id = NEW.feature_flag_id
                ) THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Environment and feature flag must belong to the same project.';
                END IF;
            END
            SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS environment_flags_project_consistency_update');
        DB::unprepared('DROP TRIGGER IF EXISTS environment_flags_project_consistency_insert');
    }
};
