<?php

enum Outcome: string
{

    case PASS = "pass";
    case FAIL = "fail";
    case REJECT = "reject";
    case SKIP = "skip";
    case UNDEFINED = "undefined";

    public function short_description(): string | null
    {
        return match ($this) {
            self::PASS => 'Успех',
            self::FAIL => 'Неудача',
            self::REJECT => 'Отклонена',
            self::SKIP => 'Пропущена',
            self::UNDEFINED => 'Ошибка',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PASS => 'green',
            self::FAIL => 'red',
            self::REJECT => 'gray',
            self::SKIP => 'gray',
            self::UNDEFINED => 'gray',
        };
    }

    public function long_description(): string
    {
        return match ($this) {
            self::PASS => 'Проверка пройдена!',
            self::FAIL => 'Проверка не пройдена!',
            self::REJECT => 'Проверка отклонена.',
            self::SKIP => 'Инструмент проверки не установлен.',
            self::UNDEFINED => 'При выполнении проверки произошла критическая ошибка.',
        };
    }
}
