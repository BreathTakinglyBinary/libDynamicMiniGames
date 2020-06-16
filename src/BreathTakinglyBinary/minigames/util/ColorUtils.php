<?php
declare(strict_types=1);

namespace BreathTakinglyBinary\minigames\util;


use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\Color;
use pocketmine\utils\TextFormat;

class ColorUtils{

    /**
     * Returns a matching meta value for a TextFormat color constant
     *
     * @param string $color a TextFormat constant
     *
     * @return int Meta value, returns -1 if failed
     */
    public static function getMetaByColor(string $color){
        switch($color){
            case TextFormat::BLACK:
                return 15;
            case TextFormat::DARK_BLUE:
                return 11;
            case TextFormat::DARK_GREEN :
                return 13;
            case TextFormat::DARK_AQUA :
            case TextFormat::AQUA :
                return 9;
            case TextFormat::DARK_RED :
            case TextFormat::RED :
                return 14;
            case TextFormat::DARK_PURPLE :
                return 10;
            case TextFormat::GOLD :
                return 1;
            case TextFormat::GRAY :
                return 8;
            case TextFormat::DARK_GRAY :
                return 7;
            case TextFormat::BLUE :
                return 3;
            case TextFormat::GREEN :
                return 5;
            case TextFormat::LIGHT_PURPLE :
                return 2;
            case TextFormat::YELLOW :
                return 4;
            case TextFormat::WHITE :
                return 0;
            default:
                return -1;
        }
    }


    /**
     * Returns a matching TextFormat color constant from meta values
     *
     * @param int $meta
     *
     * @return string $color a TextFormat constant
     */
    public static function getColorByMeta(int $meta){
        switch($meta){
            case 0:
            default:
                return TextFormat::WHITE;
            case 1:
                return TextFormat::GOLD;
            case 2:
                return TextFormat::LIGHT_PURPLE;
            case 3:
                return TextFormat::BLUE;
            case 4:
                return TextFormat::YELLOW;
            case 5:
                return TextFormat::GREEN;
            case 7:
                return TextFormat::DARK_GRAY;
            case 8:
                return TextFormat::GRAY;
            case 9:
                return TextFormat::AQUA;
            case 10:
                return TextFormat::DARK_PURPLE;
            case 11:
                return TextFormat::DARK_BLUE;
            case 13:
                return TextFormat::DARK_GREEN;
            case 14:
                return TextFormat::RED;
            case 15:
                return TextFormat::BLACK;
        }
    }


    /**
     * @param string $color a TextFormat color constant
     *
     * @return Color
     */
    public static function colorFromTextFormat($color) : Color{
        [$r, $g, $b] = str_split(ltrim(str_replace('>', '', str_replace('<span style=color:#', '', TextFormat::toHTML($color))), '#'));

        return new Color(...array_map('hexdec', [$r . $r, $g . $g, $b . $b]));
    }

    public static function setCustomColor(Item $item, Color $color){
        if(($hasTag = $item->hasCompoundTag())){
            $tag = $item->getNamedTag();
        }else{
            $tag = new CompoundTag("", []);
        }
        $tag->setInt("customColor", self::toRGB($color));
        $item->setCompoundTag($tag);

        return $item;
    }

    /**
     * Returns an RGB 32-bit colour value.
     *
     * @param Color $color
     *
     * @return int
     */
    public static function toRGB(Color $color) : int{
        return ($color->getR() << 16) | ($color->getG() << 8) | $color->getB() & 0xffffff;
    }
}