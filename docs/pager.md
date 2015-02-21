#Pagger

##Adding existing module
To add [one of existing samsonos modules](https://github.com/samsonos) you have to include string into file ```composer.json```
```php
	"require-dev": {
        "samsonos/php_pager":"*",
    }
```
In our case it is [php_pager](https://github.com/samsonos/php_pager). Than you have to update composer. Go to yor project rood directory and use: ```composer update``` command.
Now reload your project and you will have all required dependencies  installed.


##Include pagination
We have to improve our ```gallery_list``` controller so he would be able get listing parameters for our new module.
```php
/** Gallery images list controller action */
function gallery_list($sorter = null, $direction = 'ASC', $current_page = null, $page_size=4)
{

    if (!isset($current_page)) {
        // Load current page from session if it is there
        $current_page = isset($_SESSION['current_page']) ? $_SESSION['current_page'] : 1;
    }

    // Rendered HTML gallery items
    $items = '';

    // Prepare db query object
    $query = dbQuery('gallery');


    // Set the prefix for pager
    $url_prefix = "gallery/list/".$sorter."/".$direction."/";
    // Count the number of images in query
    $rows_count = $query->count();

    if (isset($current_page)) {
        // If we don't want to show all images
        if ($current_page != 0) {
            // Set the limit condition to db request
            $query->limit(($current_page - 1) * $page_size, $page_size);
            // Create a new instance of Pager
            $pager = new \samson\pager\Pager($current_page, $page_size, $url_prefix, $rows_count);
        } else {
            // Set the page size to leave Pager in the same condition
            $page_size = 4;
            $pager = new \samson\pager\Pager($current_page, $page_size, $url_prefix, $rows_count);
        }
        // Store current psge in a session
        $_SESSION['current_page'] = $current_page;
        // Create the output of Pager
        $pages = $pager->toHtml();
    }
    ...
    /* Set window title and view to render, pass items variable to view, pass pager variable to view*/
    m()->view('gallery/index')->title('My gallery')->items($items)->pager($pages);
}
```

Now we have to improve the ```gallery/index``` view to get the Pager on our page. All wee need is to add something like this ```<?php iv('pager')?>``` where ever you wish to load pager.
We will place it wright after the sorting buttons.
```php
<div class="top_menu">
    <a href="<?php url_base('gallery', 'form')?>">Upload photo</a>
        Sort by:
    <a class="sorter" href="<?php url_base('gallery', 'list', 'Loaded', 'ASC')?>">DATE ASC</a>
    <a class="sorter" href="<?php url_base('gallery', 'list', 'Loaded', 'DESC')?>">DATE DESC</a>
    <a class="sorter" href="<?php url_base('gallery', 'list', 'size', 'ASC')?>">SIZE ASC</a>
    <a class="sorter" href="<?php url_base('gallery', 'list', 'size', 'DESC')?>">SIZE DESC</a>
    <ul id="pager"><?php iv('pager')?></ul>
</div>
```