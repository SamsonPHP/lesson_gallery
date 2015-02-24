
<div class="top_menu">
   <div id="line1">
       <?php iv('gallery_sorter')?>
   </div>
    <div id="line2">
        <ul id="pager"><?php iv('pager_html')?></ul>
        <!--        Load language switcher-->
        <?php m('i18n')->render('list')?>
        <a class="btn_update" href="<?php url_base('gallery', 'list')?>">Update</a>
    </div>
</div>
<div class="gallery-container">
    <?php iv('gallery_list')?>
</div>


