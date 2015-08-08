<?php

namespace Swapbot\Repositories;

use Illuminate\Support\Facades\DB;
use Swapbot\Models\Bot;
use Swapbot\Models\BotIndex;
use \Exception;

/*
* BotIndexRepository
*/
class BotIndexRepository
{

    const FIELD_NAME        = 1;
    const FIELD_DESCRIPTION = 2;
    const FIELD_USERNAME    = 3;

    protected $table = 'bot_index';


    public static function allTypeStrings() {
        return ['name', 'description', 'username',];
    }

    public static function typeStringToInteger($type_string) {
        switch (strtolower(trim($type_string))) {
            case 'name': return self::FIELD_NAME;
            case 'description': return self::FIELD_DESCRIPTION;
            case 'username': return self::FIELD_USERNAME;
        }

        throw new Exception("unknown type: $type_string", 1);
    }

    public static function validateTypeInteger($type_integer) {
        self::typeIntegerToString($type_integer);
        return $type_integer;
    }

    public static function typeIntegerToString($type_integer) {
        switch ($type_integer) {
            case self::FIELD_NAME: return 'name';
            case self::FIELD_DESCRIPTION: return 'description';
            case self::FIELD_USERNAME: return 'username';
        }

        throw new Exception("unknown type integer: $type_integer", 1);
    }





    public function addMultipleValuesToIndex(Bot $bot, $fields_and_contents) {
        $bot_id = $bot['id'];

        $create_rows = [];
        foreach($fields_and_contents as $field_type => $contents) {
            $create_rows[] = [
                'bot_id'   => $bot_id,
                'field'    => self::validateTypeInteger($field_type),
                'contents' => $contents,
            ];
        }

        DB::table($this->table)->insert($create_rows);        
    }

    public function addToIndex(Bot $bot, $field_type, $contents) {
        $attributes = [
            'bot_id'   => $bot['id'],
            'field'    => self::validateTypeInteger($field_type),
            'contents' => $contents,
        ];

        DB::table($this->table)->insert($attributes);
    }

    public function clearIndex(Bot $bot) {
        return DB::table($this->table)
            ->where('bot_id', $bot['id'])
            ->delete();
    }

    public function indexedValue(Bot $bot, $field_type) {
        $query = DB::table($this->table)
            ->select('contents')
            ->where('bot_id', $bot['id'])
            ->where('field', self::validateTypeInteger($field_type))
            ;

        $row = $query->first();
        return (isset($row->contents) ? $row->contents : null);
    }

}
