
<div class="top_menu">
    <a href="<?php url_base('gallery', 'form')?>">Upload photo</a>
        Sort by:
    <a class="sorter" href="<?php url_base('gallery', 'list', 'Loaded', 'ASC')?>">DATE ASC</a>
    <a class="sorter" href="<?php url_base('gallery', 'list', 'Loaded', 'DESC')?>">DATE DESC</a>
    <a class="sorter" href="<?php url_base('gallery', 'list', 'size', 'ASC')?>">SIZE ASC</a>
    <a class="sorter" href="<?php url_base('gallery', 'list', 'size', 'DESC')?>">SIZE DESC</a>
    <ul id="pager"><?php iv('pager')?></ul>
</div>
<ul class="gallery">
    <?php iv('items')?>
</ul>


