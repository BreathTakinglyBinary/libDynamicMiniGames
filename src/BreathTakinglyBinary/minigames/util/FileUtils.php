<?php
declare(strict_types=1);

namespace BreathTakinglyBinary\minigames\util;


class FileUtils{

    public static function copyr($source, $dest){
        // Check for symlinks
        if(is_link($source)){
            return symlink(readlink($source), $dest);
        }

        // Simple copy for a file
        if(is_file($source)){
            return copy($source, $dest);
        }

        // Make destination directory
        if(!is_dir($dest)){
            @mkdir($dest, 0777, true);
        }

        // Loop through the folder
        $dir = dir($source);
        while(false !== $entry = $dir->read()){
            // Skip pointers
            if($entry == '.' || $entry == '..'){
                continue;
            }

            // Deep copy directories
            self::copyr("$source/$entry", "$dest/$entry");
        }

        // Clean up
        $dir->close();

        return true;
    }

}