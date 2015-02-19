
<div id="item">
    <img src="<?php iv('image_Src')?>"  title="<?php iv('image_Name')?>">
    <p>Name: <?php iv('image_Name')?></p>
    <p>Size: <?php iv('image_size')?></p>
    <p>Loaded: <?php iv('image_Loaded')?></p>

</div>


<form action="<?php url_base('gallery','save')?>" method="post" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?php iv('image_PhotoID')?>">
    <input name="name" value="<?php iv('image_Name')?>">
    <input type="file" name="file" value="<?php iv('image_Src')?>">
    <input type="submit" value="Save!">
</form>

<a href="/">Назад</a>

