# Accelerator

536 Акселератор

для работы редактора необходимо поставить node.js и https://github.com/microsoft/monaco-editor

---

# Начало работы 

## Установка hooks

```bash
pip install pre-commit
```

### Commitizen: Versioning & Commit Conventional Hook
https://dev.to/okeeffed/semantic-versioning-in-python-with-git-hooks-5c5a
https://github.com/commitizen-tools/commitizen

### 1. Установка необходимых компонентов
```bash
pip install pipenv
pipenv install commitizen==3.10.1
pipenv run cz init
pre-commit autoupdate
```

### 2. Использование хука, не дающего изменить файлы auth_ssh и update*
Скопировать скрипт ```pre-commit``` из папки ```.hooks/_localHooks``` и вставить его в папку ```.git/hooks```
В случае конфликта из-за существования такого же файла добавить расширение ```.legacy```

Установка локальных хуков в подмодуль:
```bash
pre-commit install --install-hooks
```

### В случае появления ошибки 'error: failed to push some refs to' по время push
```bash
git add .
git push
git restore -- .cz.json CHANGELOG.md
```

----

# WIKI

### Config Tools (для GUI)
```python
{
    "tools": {
        "<tool_name>": {
            "enabled": bool,
            "autoreject": bool,
            "show_to_student": bool,
            "arguments": bool,
            "check" / "checks": {
                ...
            },
            # ВАРИАТИВНЫЕ ПАРМЕТРЫ
            "language": "C++" / "C",
        },
    }
}
```