ALTER TABLE ax.ax_page ADD COLUMN type INTEGER;
UPDATE ax.ax_page SET type = 0;