# Accelerator

536 Акселератор

для работы редактора необходимо поставить node.js и https://github.com/microsoft/monaco-editor


### ЛОКАЛЬНОЕ ИСПОЛЬЗОВАНИЕ GIT-HOOKS

```console
pip install pre-commit
```

#### Commitizen: Versioning & Commit Conventional Hook
https://dev.to/okeeffed/semantic-versioning-in-python-with-git-hooks-5c5a
https://github.com/commitizen-tools/commitizen

##### 1. Установка необходимых компонентов
```console
pip install --user pipenv
pipenv install --dev pre-commit Commitizen toml
pipenv run cz init
pre-commit autoupdate
pre-commit install
```

##### 2. Использование хука, не дающего изменить файлы auth_ssh и update*
Скопировать скрипт pre-commit из папки .hooks/_localHooks и вставить его в папку .git/hooks
В случае конфликта из-за существования такого же файла добавить расширение .legacy

##### 3. Проверка истории коммитов и bump версии
```console
pipenv run cz bump
```

##### 4. Обновление CHANGELOG
```console
pipenv run cz changelog
```

##### 5. В случае появления ошибки 'error: failed to push some refs to' по время push
```console
git add .
git push
git restore -- .cz.json CHANGELOG.md
```