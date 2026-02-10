# 𝔹𝕖б-𝕔и𝕔𝕥𝕖𝕞𝕒 ди𝕔𝕥𝕒нци𝕠нн𝕠г𝕠 𝕠буч𝕖ния п𝕠 п𝕡𝕠г𝕡𝕒𝕞𝕞и𝕡𝕠в𝕒нию
![Static Badge](https://img.shields.io/badge/html-orange)
![Static Badge](https://img.shields.io/badge/css-blue)
![Static Badge](https://img.shields.io/badge/bootstrap_4-purple)
![Static Badge](https://img.shields.io/badge/javascript-yellow)
![Static Badge](https://img.shields.io/badge/nginx-green)
![Static Badge](https://img.shields.io/badge/php-blue)
![Static Badge](https://img.shields.io/badge/python-blue)
![Static Badge](https://img.shields.io/badge/postgresql-lightblue)
![Static Badge](https://img.shields.io/badge/docker%20compose-red)
![Static Badge](https://img.shields.io/badge/git_hooks-gray)

![Preview 01](/preview_01.png)
![Preview 02](/preview_02.png)
![Preview 03](/preview_03.png)

[🔗 Презентация магистерского проекта](https://docs.google.com/presentation/d/1hWgSA1VOycAtBLh89VDt2sWq1E-UR88aG3lwnP9_aGE/edit?usp=sharing)


---

# Начало работы 
для работы редактора необходимо поставить node.js и https://github.com/microsoft/monaco-editor

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
