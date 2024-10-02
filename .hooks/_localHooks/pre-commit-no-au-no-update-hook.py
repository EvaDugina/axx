#!/usr/bin/env python3

import re
import sys
import subprocess
from colorama import init

init(autoreset=True)


green_color = "\033[1;32m"
red_color = "\033[1;31m"
default_color = "\033[0m"

# Список файлов, которые должны отсутствовать в коммите
files_regex_to_exclude = [
    'auth_ssh_*', 'update.php', 'update_action.php'
]

# 
# 
# 
# 

def check_files():
    # Получаем список всех файлов в индексе
    # https://stackoverflow.com/questions/44117360/how-to-get-list-of-staged-files-for-commit-fullpaths
    proc = subprocess.Popen(['git', 'diff', '--name-only', '--cached'], stdout=subprocess.PIPE)
    staged_files = proc.stdout.readlines()
    staged_files = [f.decode('utf-8') for f in staged_files]
    staged_files = [f.strip() for f in staged_files]

    # Проверяем наличие каждого файла из списка исключений
    for file_regex in files_regex_to_exclude:
        for stage_file in staged_files:
            if re.search(file_regex, stage_file):
                print(f"pre-commit-no-au-no-update-hook: {red_color}Error! File {stage_file} can't be in commit.")
                return False

    return True

# 
# 
# 
# 

if __name__ == "__main__":
    if check_files():
        print(f"pre-commit-no-au-no-update-hook: {green_color}All files are approved.")
        sys.exit(0)
    else:
        print(f"pre-commit-no-au-no-update-hook: {red_color}Failed! Commit is rejected!")
        sys.exit(1)