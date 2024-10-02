# Accelerator

536 Акселератор

для работы редактора необходимо поставить node.js и https://github.com/microsoft/monaco-editor


### ИСПОЛЬЗОВАНИЕ GIT-HOOKS

```console
pip install pre-commit
```

#### Commitizen: Versioning & Commit Conventional Hook
https://dev.to/okeeffed/semantic-versioning-in-python-with-git-hooks-5c5a
https://github.com/commitizen-tools/commitizen

##### Установка необходимых компонентов
```console
pip install --user pipenv
pipenv install --dev pre-commit Commitizen toml
pipenv run cz init
pre-commit autoupdate
```

##### Проверка историю коммитов наизменения и пывысить версию
```console
pipenv run cz bump
```

##### Обновление CHANGELOG
```console
pipenv run cz changelog
```
