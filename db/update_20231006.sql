ALTER TABLE ax_solution_commit ADD COLUMN date_time TIMESTAMP WITH TIME ZONE;

-- Забиваем тестовые данные:
UPDATE ax_solution_commit SET date_time = now() - make_interval(hours => id);

