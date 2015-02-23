
<li>
    <a class="btn delete" title="<?php t('Delete')?>" href="<?php url_base('gallery', 'delete', 'image_PhotoID')?>">X</a>
    <a href="<?php  url_base('gallery', 'form', 'image_PhotoID')?>">
        <img src="<?php iv('image_Src')?>"  title="<?php iv('image_Name')?>">
    </a>
    <a class="btn edit" href="<?php  url_base('gallery', 'form', 'image_PhotoID')?>"><?php t('Edit')?></a>
    <span><?php iv('image_Loaded') ?></span>
    <span><?php iv('image_size') ?></span>
</li>