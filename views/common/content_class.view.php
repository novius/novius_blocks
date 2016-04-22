<?php

if (empty($value)) {
    return '';
}
$class = get_class($value);

switch ($class) {
    case 'Nos\Media\Model_Link':
        $media = $value->media;
        if (!empty($media)) {
            ?>
            <img src="<?= $media->url() ?>" style="width:100%">
        <?php
        }
        break;
        
    case 'Novius\OnlineMediaFiles\Model_Media':
        ?>
        <img src="<?= $value->thumbnail() ?>" style="width:100%">
        <?php
        break;
        
    default:
        echo "Can't preview : $class";
        break;
}
