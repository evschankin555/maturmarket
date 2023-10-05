#!/bin/bash

# Учетные данные для подключения к базе данных
DB_DRIVER="mysqli"
DB_HOSTNAME="localhost"
DB_USERNAME="maturmarket"
DB_PASSWORD="gfdhgJG_U49578-0-3J"
DB_DATABASE="maturmarket"
DB_PORT="3306"

# Префикс таблиц в базе данных (если есть)
DB_PREFIX="oc_"

# Команда для восстановления таблиц
REPAIR_COMMAND="mysqlcheck -u${DB_USERNAME} -p${DB_PASSWORD} --host=${DB_HOSTNAME} --port=${DB_PORT} --repair --all-databases"

# Выполняем восстановление таблиц
echo "Выполняется восстановление таблиц..."
$REPAIR_COMMAND

# Проверяем результат выполнения команды
if [ $? -eq 0 ]; then
  echo "Восстановление таблиц успешно завершено."
else
  echo "Ошибка восстановления таблиц."
fi
