
<div class="top_menu">
    <a href="<?php url_base('gallery', 'form')?>"><?php t('Upload photo')?></a>
        <?php t('Sort by:')?>
    <a class="sorter" href="<?php url_base('gallery', 'list', 'Loaded', 'ASC')?>"> <?php t('Date')?> ↗</a>
    <a class="sorter" href="<?php url_base('gallery', 'list', 'Loaded', 'DESC')?>"> <?php t('Date')?> ↘</a>
    <a class="sorter" href="<?php url_base('gallery', 'list', 'size', 'ASC')?>"> <?php t('Size')?> ↗</a>
    <a class="sorter" href="<?php url_base('gallery', 'list', 'size', 'DESC')?>"> <?php t('Size')?> ↘</a>
    <div id="line2">
        <ul id="pager"><?php iv('pager_html')?></ul>
        <!--        Load language switcher-->
        <?php m('i18n')->render('list')?>
    </div>
</div>
<ul class="gallery">
    <?php iv('items')?>
</ul>


