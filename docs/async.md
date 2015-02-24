#Asynchronous gallery

To provide asynchronous functionality we have to reload separate parts of ```gallery/index.php``` view with out reloading whole page. So first of all we have to change view structure.

##Creating wright views

Definitely we have to reload our list of images, so let's create new view ```gallery/list.php```:
```php
<ul class="gallery">
    <?php iv('items')?>
</ul>
```

And of course we have to change the state of sorter links asynchronously so let's create ```gallery/sorter.php``` view:
```php
<a href="<?php url_base('gallery', 'form')?>"><?php t('Upload photo')?></a>
<?php t('Sort by:')?>
<a class="sorter" href="<?php url_base('gallery', 'list', 'Loaded', 'ASC', 'current_page')?>"> <?php t('Date')?> ↗</a>
<a class="sorter" href="<?php url_base('gallery', 'list', 'Loaded', 'DESC', 'current_page')?>"> <?php t('Date')?> ↘</a>
<a class="sorter" href="<?php url_base('gallery', 'list', 'size', 'ASC', 'current_page')?>"> <?php t('Size')?> ↗</a>
<a class="sorter" href="<?php url_base('gallery', 'list', 'size', 'DESC', 'current_page')?>"> <?php t('Size')?> ↘</a>
```

Now we have to change our main view ```gallery/index.php``` in order to load all this separate views at one page.
```php
<div class="top_menu">
   <div id="line1">
   <!--        Load sorter menu-->
       <?php iv('gallery_sorter')?>
   </div>
    <div id="line2">
        <!--        Load pager-->
        <ul id="pager"><?php iv('pager_html')?></ul>
        <!--        Load language switcher-->
        <?php m('i18n')->render('list')?>
    </div>
</div>
<div class="gallery-container">
    <!-- Load list of images-->
    <?php iv('gallery_list')?>
</div>
```

##Creating asynchronous controller

There is a build in ability to create special type of controllers that are executed separately in SamsonPHP core, not like standard controllers we call them asynchronous controllers. This controller must have _async_ prefix. The main idea of async controller is that it must return asynchronous response associative array which must have one required key defined ```status```, there are two possible values ```array('status' => '1')``` or ```array('status' => '0')```. All asynchronous controllers are accessible via ```HTTP GET/POST/DELETE/PUT/UPDATE``` requests without ```_async_``` prefix but must have special ```HTTP request header``` set or ```POST/GET``` field ```SJSAsync:true```. If we have asynchronous controller action ```function gallery_async_list()``` then it will be accessible via ```HTTP GET /gallery/list```. If asynchronous controller returns ```status == '1'``` then system automatically encodes it response status array into JSON using ```json_encode()```.

###Create asynchronous ```gallery_list``` controller

In our ```controllers/gallery.php``` we have to build ```gallery_async_list()``` function, which will provide all the same functionality as ```gallery_list```, and then call this asynchronous function in our main listing function to provide initial load of the gallery.

```php
/** Gallery images list asynchronous controller action */
function gallery_async_list($sorter = null, $direction = 'ASC', $currentPage = 1, $pageSize=4)
{
    // Set the $result['status'] to 1 to provide asynchronous functionality
    $result = array('status' => 1);

    // If no sorter is passed
    if (!isset($sorter)) {
        // Load sorter from session if it is there
        $sorter = isset($_SESSION['sorter']) ? $_SESSION['sorter'] : null;
        $direction = isset($_SESSION['direction']) ? $_SESSION['direction'] : null;
    }

    if (!isset($currentPage)) {
        // Load current page from session if it is there
        $currentPage = isset($_SESSION['SamsonPager_current_page']) ? $_SESSION['SamsonPager_current_page'] : 1;
    }

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

    if (isset($sorter) && in_array($sorter, array('Loaded', 'size'))) {
        // Add sorting condition to db request
        $query->order_by($sorter, $direction);

        // Store sorting in a session
        $_SESSION['sorter'] = $sorter;
        $_SESSION['direction'] = $direction;
    }

    // Iterate all records from "gallery" table
    $items = '';
    foreach ($query->exec() as $dbItem) {
        /**@var \samson\activerecord\gallery $dbItem``` */

        /*
         * Render view(output method) and pass object received fron DB and
         * prefix all its fields with "image_", return and gather this outputs
         * in $items
         */
        $items .= m()->view('gallery/item')->image($dbItem)->output();
    }

    // Include the data about images in the result array
    $result['list'] = m()->view('gallery/list')->items($items)->output();
    // Include the data about Pager state in the result array
    $result['pager'] = $pager->toHTML();
    // Include the data about sorter links state in the result array
    $result['sorter'] = m()->view('gallery/sorter')->current_page($currentPage)->output();

    return $result;
}
```

Now let's call this function and use its results to load main view.
```php

/** Gallery images list controller action */
function gallery_list($sorter = null, $direction = 'ASC', $currentPage = 1, $pageSize=4)
{

    $gallery = gallery_async_list($sorter, $direction, $currentPage, $pageSize);

    /* Set window title and view to render, pass items variable to view, pass the Pager and current page to view*/
    m()->view('gallery/index')->title('My gallery')->gallery_list($gallery['list'])->gallery_sorter($gallery['sorter'])->pager_html($gallery['pager']);
}
```

###Building javascript

In yours project root folder you can find ```www/js/index.js``` file where you can write all necessary javascript code. There is a build in js library with a syntax similar jQuery, so it would be easy to work with this library. Just to understand how it works, let's add an update button to our ```gallery/index``` view.
```php
      <a class="btn_update" href="<?php url_base('gallery', 'list')?>">Update</a>
```
Now in our ```www/js/index.js``` we will create an ajax request to reload list of images without reloading whole page.
```javascript
s(document.body).pageInit(function(body){
    s('.btn_update').ajaxClick(function(response){
           console.log(response.status);
           console.log(response.list);
        });
});
```

In this example ```s(document.body).pageInit(function(body){``` stands for ```$(document).ready(function(){``` in jQuery. So in the console we would find an 1 and an html view of images. ```ajaxClick``` send the json encoded information and we can use the response as an object to load separate pars of our view. Now lets create the function to work with our page asynchronously. 
```javascript

var load = function(response)
{
    if (response && response.list) {
        s('.gallery-container').html(response.list);
        s('#pager').html(response.pager);
        s('#line1'.html(response.sorter);
    }
    
    // Function suppose to call itself after every asynchronous action in the page
    s('li a', pager).ajaxClick(load);
    s('.sorter').ajaxClick(load);
}
//Call this function when page is loaded for the firs time 
s('#pager').pageInit(load);
```

##Asynchronous delete function

For now we provided listing of images asynchronously. But what happens if we delete an image? The page will reload, so now we have to rebuilt ```function gallery_delete()``` controller in order to make this action asynchronous. When the image will be deleted we want to reload list of items in the page, to make it smother we will use the asynchronous controller chain. To use it we just form HTTP request consisted of both controllers calls ```GET``` ```/gallery/delete/[id]/list```. System automatically parses URL and match every asynchronous controller action call and executes each controller one by one. If one of the controllers returns status == '0' chain fails and response is generated returning HTTP 404 status. All of the asynchronous response status arrays returned by each executed controllers are merged into one single array. So let's replace ```function gallery_delete()``` with asynchronous ```function gallery_async_delete()```.
```php
function gallery_async_delete($id)
{
    // Set the result status to 0 by default 
    $result = array('status' => 0);

    /** @var \samson\activerecord\gallery $dbItem */
    $dbItem = null;
    if (dbQuery('gallery')->id($id)->first($dbItem)) {
        // Delete uploaded file
        unlink($dbItem->Src);
        // Delete DB record about this file
        $dbItem->delete();
        // If deleted change the result status to 1
        $result['status'] = 1;
    }

    return $result;

}
```

And now as we want to use asynchronous controller chain we have to change delete button request in the ```gallery/item.php``` view. 
```php
<a class="btn delete" title="<?php t('Delete')?>" href="<?php url_base('gallery', 'delete', 'image_PhotoID', 'list', 'sorter',  'direction', 'current_page')?>">X</a>
```
Now when we create a list of items in our ```gallery_async_list()``` controller we have to send ```'sorter',  'direction', 'current_page'``` variables to the ```gallery/item.php``` view. So let's modify this controller.
```php
foreach ($query->exec() as $dbItem) {
        /**@var \samson\activerecord\gallery $dbItem``` */

        /*
         * Render view(output method) and pass object received fron DB and
         * prefix all its fields with "image_", return and gather this outputs
         * in $items
         */
        $items .= m()->view('gallery/item')->image($dbItem)->sorter($sorter)->direction($direction)->current_page($currentPage)->output();
    }
```

We have to modify our ```www/js/index.js``` to provide this functionality. All we need to do is to include another ```ajaxClick``` action into our ```load``` function.
```javascript
var load = function(response)
{
    if (response && response.list) {
        s('.gallery-container').html(response.list);
        s('#pager').html(response.pager);
        s('#line1'.html(response.sorter);
    }

    s('li a', pager).ajaxClick(load);
    s('.sorter').ajaxClick(load);
    s('.delete').ajaxClick(load, function(btn){
        return confirm(s('.delete_message', btn.parent()).val());
    });
}
```
As a second parameter ```ajaxClick()``` takes ```beforeHandler``` function which returns true or false depending on confirmation of the user. If true is returned action will be executed. Now let's add a confirm message in ```gallery/item.php``` view.
```php
    <a class="btn delete" title="<?php t('Delete')?>" href="<?php url_base('gallery', 'delete', 'image_PhotoID', 'list', 'sorter',  'direction', 'current_page')?>">X</a>
    <input class="delete_message" type="hidden" value="<?php t('Delete img')?>:<?php iv('image_Name')?>">
```

We added new piece of text that suppose to be translated by our ```i18n``` module. Now we have to add the translation into ```app/i18n/dictionary.php```. Just add one more element to the array. 
```php
function dictionary()
{			
return array(	
		"ru"	=>array(
    "Delete img" => "Удалить картинку",
    ...
```

##Asynchronous upload form using ```js_tinybox``` module

First of all we have to include ```"samsonos/js_tinybox":"*",``` to the ```composer.json``` file and make a composer update. Now we can use ```tinyboxAjax()``` function which is created to work with forms. Before we go to the javascript we have to rebuild our controllers connected with upload form into asynchronous controllers.
 ```php
 /**
  * Gallery form controller action
  * @var string $id Item identifier
  */
 function gallery_async_form($id = null)
 {
     $result = array('status' => 1);
 
     /**@var \samson\activerecord\gallery $dbItem */
     $dbItem = null;
     /*
      * Try to recieve one first record from DB by identifier,
      * if $id == null the request will fail anyway, and in case
      * of success store record into $dbItem variable
      */
     if (dbQuery('gallery')->id($id)->first($dbItem)) {
         $form = m()->view('gallery/form/newfile')->image($dbItem)->output();
         // Render the form to redact item
         $result['form'] = m()->view('gallery/form/index')->title('Redact form')->image($dbItem)->form($form)->output();
     } elseif (isset($id)) {
         // File with passed ID wasn't find in DB
         $result['form'] = m()->view('gallery/form/notfoundID')->title('Not Found')->output();
     } else {
         // No ID was passed
         $result['form'] = m()->view('gallery/form/newfile')->title('New Photo')->output();
     }
     return $result;
 }
 
 /**
  * Gallery form controller action
  * @var string $id Item identifier
  */
 function gallery_async_save()
 {
     $result = array('status' => 0);
     // If we have really received form data
     if (isset($_POST)) {
 
         /** @var \samson\activerecord\gallery $dbItem */
         $dbItem = null;
 
         // Clear received variable
         $id = isset($_POST['id']) ? filter_var($_POST['id']) : null;
 
         /*
          * Try to receive one first record from DB by identifier,
          * in case of success store record into $dbItem variable,
          * otherwise create new gallery item
          */
         if (!dbQuery('gallery')->id($id)->first($dbItem)) {
             // Create new instance but without creating a db record
             $dbItem = new \samson\activerecord\gallery(false);
         }
 
 
         // Save image name
         if (isset($_POST['name'])) {
             $dbItem->Name = filter_var($_POST['name']);
             $dbItem->save();
             $result = array('status' => 1);
         }
 
         // At this point we can guarantee that $dbItem is not empty
         if (isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name'] != null) {
             $tmp_name = $_FILES["file"]["tmp_name"];
             $name = $_FILES["file"]["name"];
 
             // Create upload dir with correct rights
             if (!file_exists('upload')) {
                 mkdir('upload', 0775);
             }
 
             $src = 'upload/' . md5(time() . $name);
 
             // If file has been created
             if (move_uploaded_file($tmp_name, $src)) {
                 // Store file in upload dir
                 $dbItem->Src = $src;
                 $dbItem->size = $_FILES["file"]["size"];
                 $dbItem->Name = $name;
                 // Save image
                 $dbItem->save();
                 $result = array('status' => 1);
             }
 
         }
 
     }
 
     return $result;
 }
 
 Now in ```www/js/index.js``` we have to create javascript function to show our ```gallery/form``` view using tinybox. To make this function work asynchronously we have to call it within our ```load()``` function.
 ```javascript
 function edit(btn){
     btn.tinyboxAjax({
         // Set the response container name 
         html : 'form',
         // Close tinybox on click elsewhere besides the box
         oneClickClose : true,
         renderedHandler : function(form, tb) {
             var uploadForm = s('form', form);
             uploadForm.ajaxSubmit(function(response){
                 // Call load function after uploading the file
                 load(response);
                 // Close tinybox
                 tb.close();
             });
         }
     });
 
 }
 
 var load = function(response)
 {
     container = s('.gallery-container');
     pager = s('#pager');
     sorter = s('#line1');
     form = s('.tinybox');
 
     if (response && response.list) {
         container.html(response.list);
         pager.html(response.pager);
         sorter.html(response.sorter);
     }
 
     s('li a', pager).ajaxClick(load);
     s('.sorter').ajaxClick(load);
     s('.delete').ajaxClick(load, function(btn){
         return confirm(s('.delete_message', btn.parent()).val());
     });
     edit(s('.upload_btn'));
     edit(s('.edit'));
 }
 
 s('#pager').pageInit(load);
 ```
 
 As we want to show uploaded picture at once after uploading we have to add asynchronous controller chain call when we submit upload form. So we have to change ```form action``` in the ```gallery/form/newfile.php``` view.
 ```php
 <form action="<?php url_base('gallery', 'save', 'list', 'Loaded', 'DESC', '1')?>/" method="post" enctype="multipart/form-data">
 ```
 
 