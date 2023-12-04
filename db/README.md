1. ЗАПУСКАЕМ db_rights.bat

ДЛЯ ДАМПА live-версии БД:
2. ЗАПУСКАЕМ db_testData_live.bat
ДЛЯ ДАМПА scheme_testdata:
2. ЗАПУСКАЕМ db_restore.bat

3. Выполнить все скрипты update_*.sql 
или запустить: pg_execute_updates.bat 

4. Выполнить скрипт scheme_grant.sql