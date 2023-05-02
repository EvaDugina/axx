ALTER TABLE ax_settings ADD COLUMN github_url text;
ALTER TABLE ax_settings ADD COLUMN image_file_id INTEGER;

ALTER TABLE ax_file ADD COLUMN visibility INTEGER;
UPDATE ax_file SET visibility = 1;
-- visibility:
-- 0 - не видно студенту 
-- 1 - видно всем 
