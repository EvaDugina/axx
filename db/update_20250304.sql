ALTER TABLE ax.ax_solution_commit ADD COLUMN status integer;
UPDATE ax.ax_solution_commit SET status = 0 WHERE type = 0 OR type = 2;
UPDATE ax.ax_solution_commit SET status = 1 WHERE type = 1 OR type = 3 ;