
<li>
    <a class="btn delete" title="<?php t('Delete')?>" href="<?php url_base('gallery', 'delete', 'image_PhotoID', 'list', 'sorter',  'direction', 'current_page')?>">X</a>
    <a class="edit" href="<?php  url_base('gallery', 'form', 'image_PhotoID')?>">
        <img src="<?php iv('image_Src')?>"  title="<?php iv('image_Name')?>">
    </a>
    <a class="btn edit" href="<?php  url_base('gallery', 'form', 'image_PhotoID')?>"><?php t('Edit')?></a>
    <span><?php iv('image_Loaded') ?></span>
    <span><?php iv('image_size') ?></span>
    <input class="delete_message" type="hidden" value="<?php t('Delete img')?>:<?php iv('image_Name')?>">
</li>