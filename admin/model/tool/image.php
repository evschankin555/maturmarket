<?php
class ModelToolImage extends Model {
    public function resize($filename, $width, $height) {
        $file_path = DIR_IMAGE . $filename;

        // Проверка существования и доступности файла
        if (!is_file($file_path)) {
            $this->log->write('Файл не найден или недоступен: ' . $file_path);
            return;
        }

        if (substr(str_replace('\\', '/', realpath($file_path)), 0, strlen(DIR_IMAGE)) != str_replace('\\', '/', DIR_IMAGE)) {
            $this->log->write('Файл вне разрешенной директории: ' . $file_path);
            return;
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        $image_old = $filename;
        $image_new = 'cache/' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '-' . $width . 'x' . $height . '.' . $extension;

        $file_old_path = DIR_IMAGE . $image_old;
        $file_new_path = DIR_IMAGE . $image_new;

        if (!is_file($file_new_path) || (filemtime($file_old_path) > filemtime($file_new_path))) {
            $image_info = @getimagesize($file_old_path);

            if ($image_info === false) {
                // Логирование ошибки
                $this->log->write('Ошибка чтения изображения: ' . $file_old_path);
                return;
            }

            list($width_orig, $height_orig, $image_type) = $image_info;

            if (!in_array($image_type, array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_WEBP))) {
                $this->log->write('Неподдерживаемый тип изображения: ' . $file_old_path);
                if ($this->request->server['HTTPS']) {
                    return HTTPS_CATALOG . 'image/' . $image_old;
                } else {
                    return HTTP_CATALOG . 'image/' . $image_old;
                }
            }

            $path = '';

            $directories = explode('/', dirname($image_new));

            foreach ($directories as $directory) {
                $path = $path . '/' . $directory;

                if (!is_dir(DIR_IMAGE . $path)) {
                    if (!@mkdir(DIR_IMAGE . $path, 0777) && !is_dir(DIR_IMAGE . $path)) {
                        $this->log->write('Не удалось создать директорию: ' . DIR_IMAGE . $path);
                        return;
                    }
                }
            }

            if ($width_orig != $width || $height_orig != $height) {
                $image = new Image($file_old_path);
                $image->resize($width, $height);
                $image->save($file_new_path);
            } else {
                copy($file_old_path, $file_new_path);
            }
        }

        if ($this->request->server['HTTPS']) {
            return HTTPS_CATALOG . 'image/' . $image_new;
        } else {
            return HTTP_CATALOG . 'image/' . $image_new;
        }
    }
}
