rem create database accelerator;
rem create user accelerator with encrypted password '123456';
rem grant all privileges on database accelerator to accelerator;
psql.exe -h localhost -p 5432 -U postgres -W accelerator < scheme_testdata.sql
psql.exe -h localhost -p 5432 -U postgres < scheme_grant.sql
pause