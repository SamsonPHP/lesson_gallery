<div class="redact">
    <div id="item">
        <a class="btn delete" title="<?php t('Delete')?>" href="<?php url_base('gallery', 'delete', 'image_PhotoID')?>">X</a>
        <img src="<?php iv('image_Src')?>"  title="<?php iv('image_Name')?>">
        <p>Name: <?php iv('image_Name')?></p>
        <p>Size: <?php iv('image_size')?></p>
        <p>Loaded: <?php iv('image_Loaded')?></p>
    </div>
   <?php iv('form')?>
</div>