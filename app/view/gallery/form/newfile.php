<div class="upload_form">
    <a href="/"><?php t('Back to gallery')?></a>
    <form action="<?php url_base('gallery', 'save', 'list', 'Loaded', 'DESC', '1')?>/" method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php iv('image_PhotoID')?>">
        <?php if(isv('image_Name')):?>
            <?php t('New name')?>: <input name="name" value="<?php iv('image_Name')?>">
        <?php endif?>
        <input type="file" name="file"  <?php if(!isv('image_Name')):?> required <?php endif?> value="<?php iv('image_Src')?>">
        <input type="submit" value="<?php t('Save')?>">
    </form>
</div>
