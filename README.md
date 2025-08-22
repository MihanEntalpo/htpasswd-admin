# htpasswd admin

Web-интерфейс для редактирования `htpasswd`-файлов. Позволяет включать и отключать пользователей, менять логины, комментарии и пароли. Каждый раз перед сохранением создаётся резервная копия исходного файла, а через кнопку «Резервные копии» можно восстановить содержимое из любой сохранённой копии.

## Формат файла

Файл `.htpasswd` используется как "база данных". В нём могут встречаться строки комментариев, начинающиеся с `#`. Если после `#` находится валидная запись вида `login:hash`, такая запись считается отключённой. Несколько строк комментариев перед записью считаются комментариями к этой записи.

## Настройки

Путь к файлу и каталогу резервных копий задаётся в файле `.env`:

```
HTPASSWD_PATH=/path/to/.htpasswd
BACKUP_DIR=/path/to/backup/dir
```

Перед записью в каталог резервных копий помещается копия файла с меткой времени.

## Запуск без Docker

1. Скопируйте файлы приложения в каталог, доступный nginx.
2. Настройте nginx на проксирование запросов к PHP-FPM 7.0.
3. Укажите путь к файлу `.htpasswd` и каталогу резервных копий в `.env`.
4. Откройте страницу `index.php` в браузере.

Минимальный пример конфигурации nginx:

```
location / {
    root   /var/www/html;
    index  index.php;
}
location ~ \.php$ {
    include fastcgi_params;
    fastcgi_pass  unix:/run/php/php7.0-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

## Запуск в Docker

```
docker build -t htpasswd-admin .
docker run -p 8080:80 \
  -v /path/to/.htpasswd:/app/.htpasswd \ 
  -v /path/to/backups:/app/backups \ 
  htpasswd-admin
```

После запуска страница будет доступна на `http://localhost:8080/index.php`.

## MIT License

См. файл [LICENSE](LICENSE).
