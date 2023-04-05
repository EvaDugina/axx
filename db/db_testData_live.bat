psql.exe -d accelerator -h localhost -p 5432 -U accelerator -W -F p -E < scheme_testData_live_221212.sql
psql.exe -h localhost -p 5432 -U postgres < scheme_grant.sql