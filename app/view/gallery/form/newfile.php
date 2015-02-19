<p>Добавтьб новое фото</p>

<form action="<?php url_base('gallery', 'save')?>" method="post" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?php iv('image_PhotoID')?>">
    <input type="file" name="file" value="<?php iv('image_Src')?>">
    <input type="submit" value="Save!">
</form>

<a href="/">Назад</a>
