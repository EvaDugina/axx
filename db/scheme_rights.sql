-- run under postgres user on database accelerator
create database accelerator;
create user accelerator with encrypted password '123456';

GRANT ALL PRIVILEGES ON DATABASE accelerator TO accelerator;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO accelerator;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO accelerator;
