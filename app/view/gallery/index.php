
<a href="/gallery/form/">Добавить фото</a>

<div class="sorter">
    <a href="<?php url_base('gallery', 'list', 'Loaded', 'ASC')?>">DATE ASC</a>
    <a href="<?php url_base('gallery', 'list', 'Loaded', 'DESC')?>">DATE DESC</a>
    <a href="<?php url_base('gallery', 'list', 'size', 'ASC')?>">SIZE ASC</a>
    <a href="<?php url_base('gallery', 'list', 'size', 'DESC')?>">SIZE DESC</a>
</div>
<ul class="gallery">
    <?php iv('items')?>
</ul>


