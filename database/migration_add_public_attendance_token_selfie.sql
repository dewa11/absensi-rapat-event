ALTER TABLE events
    ADD COLUMN attendance_token VARCHAR(64) NULL UNIQUE AFTER notes,
    ADD COLUMN token_generated_at DATETIME NULL AFTER attendance_token,
    ADD COLUMN token_is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER token_generated_at;

ALTER TABLE attendances
    ADD COLUMN nip VARCHAR(32) NOT NULL AFTER participant_name;

ALTER TABLE attendances
    ADD COLUMN selfie_path VARCHAR(255) NULL AFTER unit_name;
