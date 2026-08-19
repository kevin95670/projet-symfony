<?php

namespace App\Enum;

enum Plateforme: string
{
    case ORDINATEUR = 'pc';

    case PLAYSTATION = 'playstation';
    case PLAYSTATION2 = 'playstation 2';
    case PLAYSTATION3 = 'playstation 3';
    case PLAYSTATION4 = 'playstation 4';
    case PLAYSTATION5 = 'playstation 5';
    case PSP = 'psp';
    case PSVITA = 'ps vita';

    case XBOX = 'xbox';
    case XBOX360 = 'xbox 360';
    case XBOXONE = 'xbox one';
    case XBOXSERIES = 'xbox series x|s';

    case GAMECUBE = 'gamecube';
    case WII = 'wii';
    case WIIU = 'wii u';
    case SWITCH = 'switch';
    case SWITCH2 = 'switch 2';
    case GAMEBOY = 'game boy';
    case GAMEBOYADVANCE = 'game boy advance';
    case NINTENDODS = 'nintendo ds';
    case NINTENDO3DS = 'nintendo 3ds';

    case MOBILE = 'mobile';

    public function label(): string
    {
        return match($this) {
            self::ORDINATEUR => 'PC',

            self::PLAYSTATION => 'PlayStation',
            self::PLAYSTATION2 => 'PlayStation 2',
            self::PLAYSTATION3 => 'PlayStation 3',
            self::PLAYSTATION4 => 'PlayStation 4',
            self::PLAYSTATION5 => 'PlayStation 5',
            self::PSP => 'PSP',
            self::PSVITA => 'PS Vita',

            self::XBOX => 'Xbox',
            self::XBOX360 => 'Xbox 360',
            self::XBOXONE => 'Xbox One',
            self::XBOXSERIES => 'Xbox Series X|S',

            self::GAMECUBE => 'GameCube',
            self::WII => 'Wii',
            self::WIIU => 'Wii U',
            self::SWITCH => 'Switch',
            self::SWITCH2 => 'Switch 2',
            self::GAMEBOY => 'Game Boy',
            self::GAMEBOYADVANCE => 'Game Boy Advance',
            self::NINTENDODS => 'Nintendo DS',
            self::NINTENDO3DS => 'Nintendo 3DS',

            self::MOBILE => 'Mobile',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::ORDINATEUR => 'Jeu disponible sur PC.',

            self::PLAYSTATION => 'Jeu disponible sur PlayStation.',
            self::PLAYSTATION2 => 'Jeu disponible sur PlayStation 2.',
            self::PLAYSTATION3 => 'Jeu disponible sur PlayStation 3.',
            self::PLAYSTATION4 => 'Jeu disponible sur PlayStation 4.',
            self::PLAYSTATION5 => 'Jeu disponible sur PlayStation 5.',
            self::PSP => 'Jeu disponible sur PSP.',
            self::PSVITA => 'Jeu disponible sur PS Vita.',

            self::XBOX => 'Jeu disponible sur Xbox.',
            self::XBOX360 => 'Jeu disponible sur Xbox 360.',
            self::XBOXONE => 'Jeu disponible sur Xbox One.',
            self::XBOXSERIES => 'Jeu disponible sur Xbox Series X|S.',

            self::GAMECUBE => 'Jeu disponible sur GameCube.',
            self::WII => 'Jeu disponible sur Wii.',
            self::WIIU => 'Jeu disponible sur Wii U.',
            self::SWITCH => 'Jeu disponible sur Switch.',
            self::SWITCH2 => 'Jeu disponible sur Switch 2.',
            self::GAMEBOY => 'Jeu disponible sur Game Boy.',
            self::GAMEBOYADVANCE => 'Jeu disponible sur Game Boy Advance.',
            self::NINTENDODS => 'Jeu disponible sur Nintendo DS.',
            self::NINTENDO3DS => 'Jeu disponible sur Nintendo 3DS.',

            self::MOBILE => 'Jeu disponible sur Mobile.',
        };
    }
}