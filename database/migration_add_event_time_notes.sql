ALTER TABLE events
    ADD COLUMN event_time TIME NOT NULL AFTER event_date,
    ADD COLUMN notes TEXT NOT NULL AFTER location;
