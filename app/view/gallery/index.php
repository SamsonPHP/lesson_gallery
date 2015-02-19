

<form action="<?php url_base('gallery','save')?>" method="post" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?php iv('image_PhotoID')?>">
    <input name="name" value="<?php iv('image_Name')?>">
    <input type="file" name="file" value="<?php iv('image_Src')?>">
    <input type="submit" value="Save!">
</form>

<div class="sorter">
    <a href="<?php url_base('gallery', 'list', 'Loaded', 'ASC')?>">DATE ASC</a>
    <a href="<?php url_base('gallery', 'list', 'Loaded', 'DESC')?>">DATE DESC</a>
    <a href="<?php url_base('gallery', 'list', 'size', 'ASC')?>">SIZE ASC</a>
    <a href="<?php url_base('gallery', 'list', 'size', 'DESC')?>">SIZE DESC</a>
</div>
<ul class="gallery">
    <?php iv('items')?>
</ul>


