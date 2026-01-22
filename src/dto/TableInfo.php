<?php

declare(strict_types=1);

namespace dto;

final class TableInfo
{
    /**
     * Сюда загружаются поля в TblList.
     *
     * @var array<FieldInfo>
     */
    public array $fields = [];
    /**
     * Сюда загружаются данные частично в TblList.
     *
     * @var array<array<string>>
     */
    public array $data = [];

    public function __construct(
        public ?string $Name = null,
        public ?string $Engine = null,
        public ?string $Version = null,
        public ?string $Row_format = null,
        public ?string $Rows = null,
        public ?string $Avg_row_length = null,
        public ?string $Data_length = null,
        public ?string $Max_data_length = null,
        public ?string $Index_length = null,
        public ?string $Data_free = null,
        public ?string $Auto_increment = null,
        public ?string $Create_time = null,
        public ?string $Update_time = null,
        public ?string $Check_time = null,
        public ?string $Collation = null,
        public ?string $Checksum = null,
        public ?string $Create_options = null,
        public ?string $Comment = null,
        public ?string $Charset = null,
        public ?string $Schema = null,
    ) {
    }

    /**
     * @param iterable<string> $row
     */
    public function fill(iterable $row): void
    {
        foreach ($row as $key => $value) {
            $this->{$key} = (string) $value;
        }
    }
}
