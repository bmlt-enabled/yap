ALTER TABLE conference_participants ADD INDEX `idx_conference_participants_callsid` (`callsid`);
ALTER TABLE conference_participants ADD INDEX `idx_conference_participants_conferencesid` (`conferencesid`);
ALTER TABLE records ADD INDEX `idx_records_callsid` (`callsid`);
ALTER TABLE records_events ADD INDEX `idx_records_events_service_body_id` (`service_body_id`);
ALTER TABLE records_events ADD INDEX `idx_records_events_callsid` (`callsid`);
