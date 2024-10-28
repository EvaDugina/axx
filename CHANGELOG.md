## 0.8.2 (2024-10-28)

### Fix

- **resultparse**: Использование enabled из checkres

## 0.8.1 (2024-10-28)

### Fix

- **resultparse**: Генерация pytest output

## 0.8.0 (2024-10-28)

### Feat

- **taskassign**: Пресеты параметров проверки

### Fix

- **editor**: Скорректировано сообщение, когда outcome = skip

### Refactor

- Выбор языка файла проекта из массива актуальных, опредление языка файлов

## 0.7.0 (2024-10-28)

### Feat

- **editor**: Запуск python-кода в консоли

## 0.6.2 (2024-10-27)

### Fix

- некорректные кавычки при вызове docker в textdb.php

## 0.6.1 (2024-10-27)

### Fix

- **editor**: Динамическая подгрузка полного вывода проверок

## 0.6.0 (2024-10-24)

### Feat

- Dynamic parsing python_code_check output
- **editor**: Static python_code_check output parser
- **editor**: Pytest
- **taskassign**: Pytest & Pylint configuration
- **taskedit**: Добавлена возможность прикреплять Python-файл кода теста
- **taskedit**: Добавлена возможность прикреплять к заданию несколько файлов автоматических тестов
- **taskassign**: Pytest configuretion

### Fix

- Static parsing python_code_check output
- Config pytest tools
- **textdb**: Исправление accel_ в начале файла с тестами
- **taskassgin**: Saving Pytest configuration

## 0.5.0 (2024-10-11)

### Feat

- **textdb**: Запуск на сервере модуля Python проверок

## 0.4.0 (2024-10-11)

### Feat

- **editor**: Отображение проверки Pylint
- **taskassign**: Добавлено поле конфигураций для pylint

### Fix

- **taskassign**: Сохранение конфигураций pylint
- **pre-push-commitizen-bump**: Добавлен fetch origin

## 0.3.3 (2024-10-11)

### Fix

- **common**: Ошибка с отображением CHANGELOG

## 0.3.2 (2024-10-04)

### Fix

- pre-push-commitizen-bump

## 0.3.2 (2024-10-04)

### Fix

- pre-push-commitizen-bump

## 0.3.1 (2024-10-04)

### Fix

- **env**: Исправление ошибок совместимости

## 0.3.0 (2024-10-04)

### Feat

- **common**: Modal dialog with changelog
- **common-header**: Получение текущей версии из конфига commitizen

## 0.2.2 (2024-10-04)

### Fix

- **pre-commit-config**: Исправление ошибок конфигураций

## 0.2.1 (2024-10-02)

### Fix

- Semantic Versioning with Local Git Hooks

## 0.2.0 (2024-10-02)

### Feat

- Semantic Versioniong with Git Hooks
- Hide ./idea
- **editor**: Mark Assignment with mark_type
- **taskchat**: Mark Assignment wit mark_type
- **preptable**: Mark Assignment wit mark_type
- **ColorTheme**: Добавление класса ColorTheme + Удаление Page без удаления Basic ColorThemes
- **auth**: vega auth
- **editor, taks -chat**: Отправка сообщений на Enter
- **pageedit**: Загрузка иконки раздела
- API for CL-tools added
- Page.class
- блокировка массовых операций до выбора заданий
- доделан парсер результатов проверок через js (buttons.js)
- доделан парсер результатов проверок в resultparse.php
- выгрузка предыдущих решений для copydetect
- значки индивидуального/группового назначения
- отображение полного вывода тулзов
- добавлены автотесты в параметры проверки при назначении
- обработка результатов автотестов
- редактирование и передача файлов для автотестирования
- обновление полей проверок через js
- индикация хода проверки
- stderr >> stdout
- управление инструментами при запуске проверок
- перекрашивание результатов проверок через js (in progress)
- заполнение полей проверок через js (in progress)
- обновление результатов проверок через js (заготовки)
- вызов проверок (серверная часть)
- вызов проверок (клиентская часть)
- реализовано отображение результатов проверок в editor.php
- доработан скелет для вывода результатов проверок
- цветные плашки в гармошке editor-а
- начало гармошки в editor.php
- сохранение отредактированного назначения
- обработка параметров проверки
- параметры проверки изменены
- страница редактирования назначения
- ссылка на страницу с заданиями по дисциплине
- Редактор MarkDown для описания задания

### Fix

- **update**: Обработка undefind при отсутствии outcome в результатаъ проверки
- getUserId() вместо SESSION[hash]
- Возвращение SESSION
- Working with functions auth_ssh, not with SESSION
- **POClasses**: Добавление addslashes во все места, в которых могут оказаться кавычки
- Скрытие ISSUES.txt
- work in dev mode with auth_ssh
- auth_ssh
- Вывод текстовых ошибок
- gitignore
- Время, подстроенное с учётом того, что на сервере UTC+6
- session
- session start
- **avz**: wss и db credentials
- Ссылки Редактора
- ISSUES.txt
- Видимость промежуточного коммита преподавателя + Запрет на отправку пустого коммита
- quotes while pushing autotest_results
- butons.js - check
- test.cpp -> accel_autotest.cpp
- ISSUE13
- ISSUES1-12
- taskchat - Выравнивание в сообщениях
- editor
- editor
- taskchat, taskedit, preptasks, pageedit
- taskassign issue43
- issue37,41,42
- editor - issue36
- taskchat - сообщение о проверке
- issues15,16
- taskassign - issue9
- taskedit
- beta3 issues 1-3
- textdb - отправка только файлов проекта, TASK - не показывать у студента файлы тестов
- textdb - Передаём только файлы проекта
- textdb - Пробел между отправляемыми файлами проекта и теста
- COMMIT - студент не должен просматривать прмоежуточные коммиты препода
- Отправка файлов на проверку
- icons taskchat
- taskchat
- editor
- editor, resultparse - warnings & notice
- textdb - Отправка на проверку
- editor - Клонирование коммита
- editor, textdb - Rename, Add file
- preptasks, taskchat - Отображение задания БЕСЕДЫ и удаление для неё кнопки РЕДАКТИРОВАТЬ НАЗНАЧЕНИЕ
- preptasks - Предупреждение на удаление заданий
- settings
- login - Обработка некорректного ввода
- profile_image
- textdb - Отправка файлов кода проверки тестов
- textdb - Файлы проверки
- mainpage_student - Отрицательные семестры
- taskedit | wip: textdb
- mainpage - Уведомления по разделам
- common - link fontawesome
- last sql update
- taskchat - Переход на страницу студента
- Имена файлов из БД хранятся без префикса
- textdb - SQL query
- preptasks - visibility&status buttons
- editor - filename
- preptable
- taskchat, studtasks, preptable - Исправлена и разделена логика обрабоки ax_assignment.status_code и ax_assignment.status
- pageedit, taskchat - Добавлен дефолтный выбор себя в кач-ве преподавателя. Добавлено выделение сообщений-коммитов и сообщений-оценок
- slash to UNIX
- taskedit - замена спец. символов
- takassign, taskedit, header
- taskedit - Прикрепление файла
- pageedit, mainpage - Удаление драздела, текст карточки
- pageedit, preptasks, taskassign - Добавление предупреждений на создание раздела. Исправление массовой операции прикладывания файла
- taskassign, Commit
- preptable - Отображение сообщений
- taskedit.js
- merge fixes + confirm to while deleting Task
- Small merge fixes
- textdb
- taskassign, taskedit - Добавлены проверки полей на успешное сохранение в БД
- taskchat, taskedit - Использование унифицированной функции прикрепления файлов к Объекту
- preptasks - Исправление отображения прикреплённых к заданию Assignments
- taskassign
- download_file
- taskedit
- taskassign, taskedit, preptasks
- taskedit
- taskedit
- taskedit - Debugging
- POCLasses/
- POClasses/
- taskchat, download_file - Debugging
- POClasses/
- db/
- db/
- POClasses/*
- db/
- profile
- db/ - Корректирование кода для дампа, добавление простого README. Обновления БД для использования ax_file
- profile & commin
- POClasses
- User, profile
- common, profile, User - Использование класса User в хэдере
- profile - Внедрение использования класса User
- preptable - Добавлена возмодность оценки и ответа, исправлена история посылок и аккордеон
- добавление фиксированных значений в config.json
- PHP 7 missing
- типы целых значений в confug.json
- хрень какая-то
- латинские буквы
- Редактирование преподов и групп по предмету (теперь - разделу)
- preptable - Добавлена возмодность оценки и ответа, исправлена история посылок и аккордеон
- buttons.js
- resultparse.php
- resultparse.php
- убран раздел с файлами в описании, если они отсутствуют (taskchat.php)
- транслитерация имен файлов
- убран раздел с файлами в описании, если они отсутствуют (taskassign.php)
- костыль для отображения границ в md-таблице
- pre
- code
- pre
- убрал назначение из страницы редактирования задания
- вывод задания и overflow
- hint-ы к кнопкам на странице заданий по дисциплине
- enabled теперь bool (buttons.js)
- enabled bool
- enabled внезапно строка (buttons.js)
- убрано отображение плашек с результатами, если проверка выключена (buttons.js)
- covert examples to tables in md-tasks
- сохранение неэкранированных файлов
- сохранение автотестов
- убран раздел с файлами в описании, если они отсутствуют (editor.php)
- remove --it (interactive input for docker)
- очистка предыдущего результата проверки
- span color
- рабочая директория codecheck
- пустые строки в конце php-файлов портят юзерские файлы при сохранении/открытии
- часть исправлений по аккордеону с проверками
- Удаление назначений
- preptasks, taskedit - Перемещение в архив и удаление задания
- taskedit - К предыдущему коммиту
- taskedit, praptasks - Архивирование и Разархивирование задания вместо удаления
- taskedit
- отображение имён дисциплин
- порядок файлов-приложений к заданию
- типы прикладываемых к заданию файлов
- transparent pagecard spans
- common - Header notify
- pageedit, mainpage - Исправлена работа с семестром, issue #46
- добавление первой страницы
- profile - fio
- student_user_id + zavjalov-message-link
- auth, common, settings - Исправлен механизм авторизации. Исправление ошибки пустой перезаписи перем.
- taskchat - Исправлен механизм прочтения сообщения
- header - Список уведомлений, исправлено дублирование
- mainpage
- pageedit - Родила царица в ночь, не то сына ни то дочь ДОБАВЛЕНИЕ СЕМЕСТРА ПРЕДМЕТА В БД
- pageedit
- pegeedit - Добавление в нужный семестр
- revert предыдущий
- Исправлена ф-ция get_semester из БД
- common - Исправление вывода имени и фамилии пользователя в header
- taskchat, dbqueries - Исправлено disabled форм отправки и оценки ответа + функции из дб
- profile, pageedit - Исправлено отображение имени и фамилии студентов
- preptable - Исправлено отображение сообщений
- studtasks
- preptable - Оценивание ответа
- неверно указывался тип задания при редактировании
- добавление в неправильный семестр
- работа с файлами
- работа с файлами
- remove nonascii before copydetect
- отправка кода на проверку
- заполнение семестра при добавлении дисциплины
- разбиение на главной странице препода по семестрам
- отправка на проверку
- utilities - ИСправление конвертации года в порядковый номер семестра
- pageedit, utilities - Добавление задания
- taskchat, taskchat_action, preptable - 11-ый пункт
- taskchat - Удаление лишних echo
- pageedit - Исправлен выбор семестра
- preptable, utilities - Исправлено создание дисциплины и её отображение
- editor - Добавлены обработчики ошибок. Загружается без ошибок
- taskedit, taskchat - Исправлена функция сохранения в БД полного текста файла
- download_file - Исправление ошибки отображения на стр. taskchat в разделе файлов, приложенных к ответу фалов с кодами теста
- taskedit, taskchat - Исправлено добавление файла и его содержимого в бд
- taskedit - Исправлен подсчёт количества прикрепляемых файлов, добавлена пересылка на стр. preptasks
- preptable - Исправления работы выпадающего списка
- Добавление в проект функций работы с названием загружаемого файла, формирующие уникальный префикс
- editor - Скорректировано отображение списка файлов текущего коммита
- array_column не поддерживается php 5.4
- editor, реализована функция загрузки последних ax_solution_files
- taskchat, Реализована ф-ция добавление файлов-ответов в ax_solution_file, почищен и сокращён код task_action
- editor, Подключены все зависимости, слегка отредактирован код
- Удаление upload_files
- header, Исправление ошибки удваивания уведомлений
- preptable, Исправление кол-ва уведомлений в выпадающем списке студентов
- header, встраивание функций show_head и show_header
- preptable, Исправление работы таблицы и всплывающих окон оценивания заданий
- taskchat, Исправлена работа системы прочтений сообщений
- taskedit.php, Исправление работы функции прикрепления дедлайна
- Изменение описания таблицы ax_color_theme в scheme_testdata.sql

### Refactor

- функции разбора json с результатами тестов вынесены в отдельный файл
