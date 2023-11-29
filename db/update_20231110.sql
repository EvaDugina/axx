CREATE SCHEMA ax;

ALTER TABLE ax_page SET SCHEMA ax;
ALTER TABLE ax_page_group SET SCHEMA ax;
ALTER TABLE ax_page_prep SET SCHEMA ax;
ALTER TABLE ax_task SET SCHEMA ax;
ALTER TABLE ax_task_file SET SCHEMA ax;
ALTER TABLE ax_task SET SCHEMA ax;
ALTER TABLE ax_task_file SET SCHEMA ax;
ALTER TABLE ax_assignment SET SCHEMA ax;
ALTER TABLE ax_assignment_session SET SCHEMA ax;
ALTER TABLE ax_assignment_student SET SCHEMA ax;
ALTER TABLE ax_solution_commit SET SCHEMA ax;
ALTER TABLE ax_solution_file SET SCHEMA ax;
ALTER TABLE ax_message SET SCHEMA ax;
ALTER TABLE ax_message_attachment SET SCHEMA ax;
ALTER TABLE ax_message_delivery SET SCHEMA ax;
ALTER TABLE ax_message_file SET SCHEMA ax;
ALTER TABLE ax_settings SET SCHEMA ax;
ALTER TABLE ax_student_page_info SET SCHEMA ax;
ALTER TABLE ax_file SET SCHEMA ax;
ALTER TABLE ax_commit_file SET SCHEMA ax;
ALTER TABLE students_to_subgroups SET SCHEMA ax;
ALTER TABLE ax_autotest_results SET SCHEMA ax;
ALTER TABLE ax_color_theme SET SCHEMA ax;

GRANT ALL PRIVILEGES ON SCHEMA ax TO accelerator;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA ax TO accelerator;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA ax TO accelerator;