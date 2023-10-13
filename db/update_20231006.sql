ALTER TABLE ax_solution_commit ADD COLUMN date_time TIMESTAMP WITH TIME ZONE;

-- Забиваем тестовые данные:
UPDATE ax_solution_commit SET date_time = now() - interval '106 hour' + interval '1 hour' * id;

UPDATE ax_file SET visibility = 0 WHERE type = 2 OR type = 3;
 
