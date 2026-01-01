<?php

declare(strict_types=1);

namespace service;

/**
 *
 */
class Utils
{
    /**
     * Преобразует размер в байтах в строковое смотрибельное представление в форме " .. Kb .. Mb"
     *
     * @param integer $bytes Размер файла в байтах
     * @return string Строковое представление
     * @package number
     */
    public static function formatSize(int $bytes): string
    {
        if ($bytes < pow(1024, 1)) {
            return "$bytes b";
        } elseif ($bytes < pow(1024, 2)) {
            return round($bytes / pow(1024, 1), 2) . ' Kb';
        } elseif ($bytes < pow(1024, 3)) {
            return round($bytes / pow(1024, 2), 2) . ' Mb';
        } elseif ($bytes < pow(1024, 4)) {
            return round($bytes / pow(1024, 3), 2) . ' Gb';
        } else {
            return "$bytes b";
        }
    }

    /**
     * Получить максимальный допустимый размер аплоада файла
     *
     * @return integer Размер в байтах
     */
    public static function getMaxUploadSize(): int
    {
        if (!$filesize = ini_get('upload_max_filesize')) {
            $filesize = "5M";
        }
        $max_upload_size = self::getRealSize($filesize);
        if ($postSize = ini_get('post_max_size')) {
            $postSize = self::getRealSize($postSize);
            if ($postSize < $max_upload_size) {
                $max_upload_size = $postSize;
            }
        }
        return $max_upload_size;
    }

    /**
     * Возвращает реальный размер в байтах строкового php ini  представления числа
     *
     * @param string $size Строковое php ini представление
     * @return int Размер файла в байтах
     */
    public static function getRealSize(string $size): int
    {
        if (!$size) {
            return 0;
        }
        $scan['MB'] = 1048576;
        $scan['Mb'] = 1048576;
        $scan['M'] = 1048576;
        $scan['m'] = 1048576;
        $scan['KB'] = 1024;
        $scan['Kb'] = 1024;
        $scan['K'] = 1024;
        $scan['k'] = 1024;
        foreach (array_keys($scan) as $key) {
            if ((strlen($size) > strlen($key)) && (str_ends_with($size, $key))) {
                $int = (int)substr($size, 0, strlen($size) - strlen($key));
                $size = $int * $scan[$key];
                break;
            }
        }
        return (int)$size;
    }
}
