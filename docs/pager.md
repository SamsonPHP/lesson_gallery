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
function gallery_list($sorter = null, $direction = 'ASC', $currentPage = null, $pageSize=4)
{
    // If no sorter is passed
    if (!isset($sorter)) {
        // Load sorter from session if it is there
        $sorter = isset($_SESSION['sorter']) ? $_SESSION['sorter'] : null;
        $direction = isset($_SESSION['direction']) ? $_SESSION['direction'] : null;
    }

    if (!isset($currentPage)) {
        // Load current page from session if it is there
        $currentPage = isset($_SESSION['current_page']) ? $_SESSION['current_page'] : 1;
    }

    // Rendered HTML gallery items
    $items = '';

    // Prepare db query object
    $query = dbQuery('gallery');

    // Set the prefix for pager
    $urlPrefix = "gallery/list/".$sorter."/".$direction."/";
    // Count the number of images in query
    $rowsCount = $query->count();

    // Create a new instance of Pager
    $pager = new \samson\pager\Pager($currentPage, $pageSize, $urlPrefix, $rowsCount);

    // Set the limit condition to db request
    $query->limit($pager->start, $pager->end);

    // Store current page in a session
    $_SESSION['current_page'] = $currentPage;

    ...

    /* Set window title and view to render, pass items variable to view, pass the Pager to view*/
    m()->view('gallery/index')->title('My gallery')->items($items)->pager($pager);
}
```

Now we have to improve the ```gallery/index``` view to get the Pager on our page. All wee need is to add something like this ```<?php iv('pager_html')?>```, where ```pager``` - variable we set the controller and ```_html`` - prefix we use to call method ```toView()``` from a Pager.
We will place it wright after the sorting buttons.
```php
<div class="top_menu">
    <a href="<?php url_base('gallery', 'form')?>">Upload photo</a>
        Sort by:
    <a class="sorter" href="<?php url_base('gallery', 'list', 'Loaded', 'ASC')?>">DATE ASC</a>
    <a class="sorter" href="<?php url_base('gallery', 'list', 'Loaded', 'DESC')?>">DATE DESC</a>
    <a class="sorter" href="<?php url_base('gallery', 'list', 'size', 'ASC')?>">SIZE ASC</a>
    <a class="sorter" href="<?php url_base('gallery', 'list', 'size', 'DESC')?>">SIZE DESC</a>
    <ul id="pager"><?php iv('pager_html')?></ul>
</div>
```