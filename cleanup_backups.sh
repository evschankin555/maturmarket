#!/bin/bash

# Получаем текущий месяц и год
current_month=$(date +%m)
current_year=$(date +%Y)

# Проверяем, не первое ли сегодня число
if [ "$(date +%d)" != "01" ]; then
    # Вычисляем прошлый месяц и год
    last_month=$(date --date="$(date +%Y-%m-01) -1 day" +%m)
    last_month_year=$(date --date="$(date +%Y-%m-01) -1 day" +%Y)

    # Формируем шаблон имени файла для удаления
    file_pattern="maturmarket-${last_month_year}-${last_month}*"

    # Получаем список файлов, соответствующих шаблону
    files_to_delete=$(find /home/maturmarket/backups/ -type f -name "$file_pattern")

    # Проверяем, есть ли файлы для удаления
    if [ -n "$files_to_delete" ]; then
        # Выводим сообщение о начале удаления
        echo "Начало удаления файлов:"

        # Перебираем файлы и удаляем их
        for file in $files_to_delete; do
            echo "Удаление файла: $file"
            sudo rm -f "$file"

            # Проверяем статус удаления
            if [ $? -eq 0 ]; then
                echo "Файл успешно удален"
            else
                echo "Ошибка при удалении файла"
            fi
        done

        # Выводим сообщение об окончании удаления
        echo "Удаление файлов завершено."
    else
        echo "Нет файлов для удаления."
    fi
fi

# Директория, где хранятся файлы бэкапа
BACKUP_DIR="/home/maturmarket/backups"

# Находим все файлы .sql в директории бэкапов и архивируем их
find $BACKUP_DIR -type f -name "*.sql" -exec gzip "{}" \;

echo "Все файлы .sql были архивированы."


# Получаем текущий день месяца
CURRENT_DAY=$(date +%d)

# Проверяем, совпадает ли текущий день с 2-м или 3-м числом
if [[ "$CURRENT_DAY" == "02" || "$CURRENT_DAY" == "03" ]]; then
    # Находим и удаляем архивированные (.gz) файлы логов в /var/log
    find /var/log -type f -name "*.gz" -delete
fi
