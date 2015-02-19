
<li>
    <img src="<?php iv('image_Src')?>"  title="<?php iv('image_Name')?>">
    <a class="btn edit" href="<?php  url_base('gallery', 'form', 'image_PhotoID')?>">Редактировать</a>
    <a class="btn delete" href="<?php url_base('gallery', 'delete', 'image_PhotoID')?>">Удалить</a>
    <span><?php iv('image_Loaded') ?></span>
    <span><?php iv('image_size') ?></span>
</li>