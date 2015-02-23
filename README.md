#Creating Gallery application
We consider that you have successfully created [samsonos/htmlapp project](https://github.com/samsonos/htmlapp) project and get it running without any errors and notices.

##Creating gallery module controller
First of all you should create ```www/app/controller/gallery.php```, with your first gallery controller action ```list```, which will be accessible via url: ```/gallery/list```:

```php 
/** Gallery images list controller action */
function gallery_list()
{
    /** Set window title and view to render */
    m()->title('My gallery')->view('gallery/index');
}

```

In this controller action example we have received current module object using ```m()``` method which is actually a shortcut for ```s()->module()```, where ```s()``` is shortcut for receiving current ```samson\core\Core``` object instance.

> All shortcut function are optimized to use static variables so they almost have no overhead.

After receiving current module object, that actually will be a ```gallery``` module, you can check it via ```trace(m()->id())``` if you do not believe me, we have setted module view file path. This file path will be used when module ```render()``` method will be called. This is usually happening in main template file(in our example this is ```/www/app/view/index.php```, you can see ```m()->render()``` there, this is the exact place where your module will be rendered).

Default SamsonPHP application comes with built in ```main``` module, and it is set to application entry to it, what module will be opened when you enter your domain without any parameters? This is set in application init script ```/www/index.php``` in ```->start([module_name])```, we call it *default module*.

So lets change our default module to ```gallery``` -> ```->start('gallery')```. If we now try to open our project we will see an error that *..no view has been set...*, this is happening because we did not specified neither default GET controller nor universal controller - controllers that can handle this empty routes without any parameters. So lets create universal gallery controller in gallery module controller file:
```php
/** Gallery universal controller */
function gallery__HANDLER()
{
   // Call our list controller
   gallery_list();
}
```
Here we created universal gallery controller which will handle empty url and render our gallery list controller. Now try to open your project domain without any parameters.

##Creating and manipulating views
Ok, now we need to start showing something in our list, lets create gallery folder in our views folder ```/www/app/view/gallery```. We need to view files:
* ```/www/app/view/gallery/index.php``` - Main gallery view, will be used to show all items
* ```/www/app/view/gallery/item.php``` - Single gallery item view, shows one gallery item

###Gallery index view ```/www/app/view/gallery/index.php```
```php
<ul class="gallery">
 <?php iv('items')?>
</ul>
```
We have created HTML ul block with class ```gallery``` and have used special view shortcut ```iv()``` this is ment - Is value set? Output it! So now our gallery index view is waiting for us to pass ```items``` variable to it.

###Gallery item view ```/www/app/view/gallery/item.php```
```php
<li>
    <img src="<?php iv('image_Src')?>"  title="<?php iv('image_Name')?>">
    <span><?php iv('image_Loaded') ?></span>
    <span><?php iv('image_size') ?></span>
</li>
```
We have created HTML li block and defined to output 4 image parameters:
* ```image_Src``` - Path to image
* ```image_Name``` - Image string name
* ```image_Loaded``` - When was it loaded
* ```image_size``` - Image size

## Passing parameters to view and rendering subviews
Now we need to render everything, application template has build in [SamsonPHP ActiveRecord](https://github.com/samsonos/php_activerecord) support. In your DB you already  have ```gallery``` table with:
* ```PhotoID``` - Primary, Autoincrement identifier field
* ```Name``` - varchar(255), image name
* ```Src``` - varchar(255), path to image
* ```size``` - int(11), size of uploaded image
* ```Loaded``` - timestamp, CURRENT_TIMESTAMP by default

If you want to create new table you need to after table creation and reload your project, all the ActiveRecord classes would be automatically created in ```/www/app/cache/db/...``` folders and you can just use them without any additional line of your code.
So lets change our list controller action:
```php 
/** Gallery images list controller action */
function gallery_list()
{
    // Rendered HTML gallery items
    $items = '';

    // Iterate all records from "gallery" table
    foreach (dbQuery('gallery')->exec() as $dbItem) {
       /**@var \samson\activerecord\gallery $dbItem``` */
       
       /* Render view(output method) and pass object received fron DB and
        * prefix all its fields with "image_", return and gather this outputs
        * in $items
        */
       $items .= m()->view('gallery/item')->image($dbItem)->output();
    }

    /** Set window title and view to render, pass items variable to view */
    m()->view('gallery/index')->title('My gallery')->items($items);
}
```

Now you must add database object manually to your ```gallery``` table, and see what will be outputted on your projects main page.

##Uploading images
Now we need ability to load images without interacting with database manually, we need to:
* Create form for adding gallery items
* Create controller action to store this item to a database

###Creating a form
We need to add new controller action ```form``` to our gallery controller file ```/www/app/controller/gallery.php```, as programmers are very clever and also lazy, our form will met two purposes:
* Creating a new item if no identifier is passed
* Editing existing item if identifier is passed

```php
/** 
 * Gallery form controller action 
 * @var string $id Item identifier
 */
function gallery_form($id = null)
{
   /*@var \samson\activerecord\gallery $dbItem */
   $dbItem = null;
   /* 
    * Try to recieve one first record from DB by identifier,
    * if $id == null the request will fail anyway, and in case
    * of success store record into $dbItem variable
    */
   if (dbQuery('gallery')->id($id)->first($dbItem)) {
      // Handle success
   }

   // Set view file, title and pass, if it os set, found gallery item
   m()->view('gallery/form')->title('Gallery form')->image($dbItem);
}
```
Now we need to create gallery form view file:
```php 
<form action="<?php url_base('gallery','save')?>" method="post">
 <input type="hidden" name="id" value="<?php iv('image_PhotoID')?>">
 <input name="name" value="<?php iv('image_Name')?>">
 <input type="file" name="file" value="<?php iv('image_Src')?>">
 <input type="submit" value="Save!">
</form>
```
Point your attention to ```<form action="..."``` attribute, this is route which must handle form submition. The first field in our form is ```hidden``` and storing image db identifier, using this field we will understand if this is a new item or existing one.

###Storing form data
As we specified in our form. we must handle ```gallery/save``` route to receive form data, lets create gallery ```save``` controller action:
```php
/** 
 * Gallery form controller action 
 * @var string $id Item identifier
 */
function gallery_save()
{
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
        $dbItem->Name = filter_var($_POST['name']);
        $dbItem->save();

        // At this point we can guarantee that $dbItem is not empty
        if (isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name'] != null) {
            $tmp_name = $_FILES["file"]["tmp_name"];
            $name = $_FILES["file"]["name"];

            // Create upload dir with correct rights
            if (!file_exists('upload')) {
                mkdir('upload', 0775);
            }

            // Set the unique name for uploaded files
            $src = 'upload/' . md5(time() . $name);

            // If file has been created
            if (move_uploaded_file($tmp_name, $src)) {
                // Store file in upload dir
                $dbItem->Src = $src;
                $dbItem->size = $_FILES["file"]["size"];
                $dbItem->Name = $name;
                // Save image
                $dbItem->save();
            }

        }

    }

    // Redirect to main page
    url()->redirect();
}

```

##Manipulating gallery item
Also we need to perform several actions with our existing gallery items:
* Edit existing item
* Delete existing item

###Deleting gallery item
We will create ```delete``` controller action ```gallery/delete/{id}``` in ```/www/app/controller/gallery.php```
```php
/**
 * Delete controller action
 *@var string $id Item db identifier
 */
function gallery_delete($id)
{
    /** @var \samson\activerecord\gallery $dbItem */
    $dbItem = null;
    if (dbQuery('gallery')->id($id)->first($dbItem)) {
        // Delete uploaded file
        unlink($dbItem->Src);
        // Delete DB record about this file
        $dbItem->delete();
    }

    // Go to main page
    url()->redirect();
}
```

###Editing gallery item
We need to modify our ```gallery/item``` view to add needed buttons(```/www/app/view/galley/item.php```):
```php
<li>
    <a class="btn delete" href="<?php url_base('gallery', 'delete', 'image_PhotoID')?>">X</a>
    <a href="<?php  url_base('gallery', 'form', 'image_PhotoID')?>">
        <img src="<?php iv('image_Src')?>"  title="<?php iv('image_Name')?>">
    </a>
    <a class="btn edit" href="<?php  url_base('gallery', 'form', 'image_PhotoID')?>">Edit</a>
    <span><?php iv('image_Loaded') ?></span>
    <span><?php iv('image_size') ?></span>
</li>
```

Now we have to improve our ```gallery/form``` controller action, to edit existing file. For this purpose we have to create new view to interact with image we get from DB.
Let's call it ```form.php```. We have to load selected image, some information about it and a form to edit this image.
```php
<div id="item">
    <a class="btn delete" href="<?php url_base('gallery', 'delete', 'image_PhotoID')?>">X</a>
    <img src="<?php iv('image_Src')?>"  title="<?php iv('image_Name')?>">
    <p>Name: <?php iv('image_Name')?></p>
    <p>Size: <?php iv('image_size')?></p>
    <p>Loaded: <?php iv('image_Loaded')?></p>

</div>

<div class="upload_form">
    <a href="/">Back to gallery</a>
    <form action="<?php url_base('gallery','save')?>" method="post" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?php iv('image_PhotoID')?>">
    New name: <input name="name" value="<?php iv('image_Name')?>">
    <input type="file" name="file" value="<?php iv('image_Src')?>">
    <input type="submit" value="Save!">
</form>
</div>
```

In our ```gallery/form``` controller we have to render view ```form.php``` if someone called this action with ```id```` parameter.

```php
/**
 * Gallery form controller action
 * @var string $id Item identifier
 */
function gallery_form($id = null)
{
    /*@var \samson\activerecord\gallery $dbItem */
    $dbItem = null;
    /*
     * Try to recieve one first record from DB by identifier,
     * if $id == null the request will fail anyway, and in case
     * of success store record into $dbItem variable
     */

    if (dbQuery('gallery')->id($id)->first($dbItem)) {
        // Render the form to redact item
        m()->view('gallery/form')->title('Redact form')->image($dbItem);
    }

     m()->view('gallery/form')->title('Gallerty form')->image($dbItem);
}
```

In case to prevent errors when somebody will try to access not existing image we have to render another view. Since this just another view of this module it's better to create ```www/app/view/gallery/form``` folder and place our ```gallery/form.php``` view in this folder and call it ```index.php```. Than Create a new view ```gallery/form/notfoundID.php```.

```php
<div class="upload_form">
    <p>Item not found!
        <a href="/">Back to gallery</a></p>
</div>
```
If someone tries to access this controller without passing any ```id``` we should view a form to upload new photo. So we have to create another view for this controller ```/www/app/view/gallery/form/newfile.php```.
```php
<div class="upload_form">
    <a href="/">Back to gallery</a>
    <form action="<?php url_base('gallery', 'save')?>" method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php iv('image_PhotoID')?>">
        <input type="file" name="file" value="<?php iv('image_Src')?>">
        <input type="submit" value="Save!">
    </form>
</div>
```

Now we have to improve our controller ``gallery/form``` so that it could show all this views in case they are needed.
```php
/**
 * Gallery form controller action
 * @var string $id Item identifier
 */
function gallery_form($id = null)
{
    /*@var \samson\activerecord\gallery $dbItem */
    $dbItem = null;
    /*
     * Try to recieve one first record from DB by identifier,
     * if $id == null the request will fail anyway, and in case
     * of success store record into $dbItem variable
     */

    if (dbQuery('gallery')->id($id)->first($dbItem)) {
        // Render the form to redact item
        m()->view('gallery/form/index')->title('Redact form')->image($dbItem);
    } elseif (isset($id)) {
        // File with passed ID wasn't find in DB
        m()->view('gallery/form/notfoundID')->title('Not Found');
    } else {
        // No ID was passed
        m()->view('gallery/form/newfile')->title('New Photo');
    }
}
```

Now when we created a separated view for adding a new photo it's better to remove form from ```gallery/index.php``` and attach a link to this form instead.
```php

<div class="top_menu">
    <a href="<?php url_base('gallery', 'form')?>">Upload photo</a>
</div>
<ul class="gallery">
    <?php iv('items')?>
</ul>
```
We have used for the first time special shortcut ```url_base```, which will always generate correct url for you.

##Sorting gallery list
Another very useful and commonly used feature is sorting, lets add this feature to our gallery ```list``` controller action,
first we need to create two parameters, lets imagine that we have two criteria for sorting:
* sorting by date
* sorting by image size

Also we need to add sorting direction *Ascending* or *Descending*, this will result in two additional URL parameters for our
controller action.

At this point we hope that you can manually add this fields to your database and modify ```save``` action to store them in database,
so lets modify our ```/www/app/controller/gallery.php```:
```php
/** Gallery images list controller action */
function gallery_list($sorter = null, $direction = 'ASC')
{
    // Rendered HTML gallery items
    $items = '';

    // Prepare db query object
    $query = dbQuery('gallery');

    // If sorter is passed
    if (isset($sorter) && in_array($sorter, array('date', 'type'))) {
        // Add sorting condition to db request
        $query->order_by($sorter, $direction);
    }

    // Iterate all records from "gallery" table
    foreach ($query->exec() as $dbItem) {
       /**@var \samson\activerecord\gallery $dbItem``` */

       /* Render view(output method) and pass object received fron DB and
        * prefix all its fields with "image_", return and gather this outputs
        * in $items
        */
       $items .= m()->view('gallery/item')->image($dbItem)->output();
    }

    /** Set window title and view to render, pass items variable to view */
    m()->view('gallery/index')->title('My gallery')->items($items);
}
```

Also we need to add this new sorter buttons to our main gallery index view ```/www/app/view/gallery/index.php```
```php
<div class="top_menu">
    <a href="<?php url_base('gallery', 'form')?>">Upload photo</a>
        Sort by:
    <a class="sorter" href="<?php url_base('gallery', 'list', 'Loaded', 'ASC')?>">DATE ASC</a>
    <a class="sorter" href="<?php url_base('gallery', 'list', 'Loaded', 'DESC')?>">DATE DESC</a>
    <a class="sorter" href="<?php url_base('gallery', 'list', 'size', 'ASC')?>">SIZE ASC</a>
    <a class="sorter" href="<?php url_base('gallery', 'list', 'size', 'DESC')?>">SIZE DESC</a>
</div>
<ul class="gallery">
 <?php iv('items')?>
</ul>
```

But what about saving our state of sorter, what if we have realoded page, our went to home page,
do we still want for our sorting order to be the same - YES! For this we need to store our sorter
in ```$_SESSION```, lets modify ```/www/app/controller/gallery.php```:
```php
/** Gallery images list controller action */
function gallery_list($sorter = null, $direction = 'ASC')
{
    // If no sorter is passed
    if (!isset($sorter)) {
        // Load sorter from session if it is there
        $sorter = isset($_SESSION['sorter']) ? $_SESSION['sorter'] : null;
        $direction = isset($_SESSION['direction']) ? $_SESSION['direction'] : null;
    }

    // Rendered HTML gallery items
    $items = '';

    // Prepare db query object
    $query = dbQuery('gallery');

    // If sorter is passed

    if (isset($sorter) && in_array($sorter, array('Loaded', 'size'))) {
        // Add sorting condition to db request
        $query->order_by($sorter, $direction);

        // Store sorting in a session
        $_SESSION['sorter'] = $sorter;
        $_SESSION['direction'] = $direction;
    }

    // Iterate all records from "gallery" table
    foreach ($query->exec() as $dbItem) {
        /**@var \samson\activerecord\gallery $dbItem``` */

        /*
         *   Render view(output method) and pass object received fron DB and
         * prefix all its fields with "image_", return and gather this outputs
         * in $items
         */
        $items .= m()->view('gallery/item')->image($dbItem)->output();
    }

    /* Set window title and view to render, pass items variable to view */
    m()->view('gallery/index')->title('My gallery')->items($items);
}
```

