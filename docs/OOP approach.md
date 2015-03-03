#Convert gallery into external module

For this goal we have to create a folder ```src/gallery```. In this folder create main controller ```src/Controller.php``` and execute ```composer dumpautload``` from the commandline in the project root directory. Than you have to place all functionality from ```app/controller/gallery.php``` in this controller. Here we have to create a ```class Gallery``` with ``` protected $id = 'gallery';``` and replace all ```gallery``` int the methods names to ```_```. 
```php
<?php

// Set the namespace
namespace gallery;


class Gallery extends \samson\core\CompressableExternalModule
{
    protected $id = 'gallery';

    /** Gallery images list controller action */
    public function __list($sorter = null, $direction = 'ASC', $currentPage = 1, $pageSize = 4)
    {

        $gallery = $this->__async_list($sorter, $direction, $currentPage, $pageSize);

        /* Set window title and view to render, pass items variable to view, pass the Pager and current page to view*/
        $this->view('index')->title('My gallery')->gallery_list($gallery['list'])->gallery_sorter($gallery['sorter'])->pager_html($gallery['pager']);
    }

    /** Gallery universal controller */
    public function __HANDLER()
    {
        // Call our lsit controller
        $this->__list();
    }

    /** Gallery images list asynchronous controller action */
    public function __async_list($sorter = null, $direction = 'ASC', $currentPage = 1, $pageSize = 4)
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
            $items .= $this->view('item')->image($dbItem)->sorter($sorter)->direction($direction)->current_page($currentPage)->output();
        }

        // Include the data about images in the result array
        $result['list'] = $this->view('list')->items($items)->output();
        // Include the data about Pager state in the result array
        $result['pager'] = $pager->toHTML();
        // Include the data about sorter links state in the result array
        $result['sorter'] = m()->view('sorter')->current_page($currentPage)->output();

        return $result;
    }

    /**
     * Gallery form controller action
     * @var string $PhotoID Item identifier
     */
    public function __async_form($PhotoID = null)
    {
        $result = array('status' => 1);

        /**@var \samson\activerecord\gallery $dbItem */
        $dbItem = null;
        /*
         * Try to recieve one first record from DB by identifier,
         * if $id == null the request will fail anyway, and in case
         * of success store record into $dbItem variable
         */
        if (dbQuery('gallery')->id($PhotoID)->first($dbItem)) {
            $form = $this->view('/form/newfile')->image($dbItem)->output();
            // Render the form to redact item
            $result['form'] = $this->view('/form/index')->title('Redact form')->image($dbItem)->form($form)->output();
        } elseif (isset($PhotoID)) {
            // File with passed ID wasn't find in DB
            $result['form'] = $this->view('/form/notfoundID')->title('Not Found')->output();
        } else {
            // No ID was passed
            $result['form'] = $this->view('/form/newfile')->title('New Photo')->output();
        }
        return $result;
    }

    /**
     * Gallery form controller action
     * @var string $PhotoID Item identifier
     */
    public function __async_save()
    {
        $result = array('status' => 0);
        // If we have really received form data
        if (isset($_POST)) {

            /** @var \samson\activerecord\gallery $dbItem */
            $dbItem = null;

            // Clear received variable
            $PhotoID = isset($_POST['id']) ? filter_var($_POST['id']) : null;

            /*
             * Try to receive one first record from DB by identifier,
             * in case of success store record into $dbItem variable,
             * otherwise create new gallery item
             */
            if (!dbQuery('gallery')->id($PhotoID)->first($dbItem)) {
                // Create new instance but without creating a db record
                $dbItem = new \samson\activerecord\gallery(false);
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

            // Save image name
            if (isset($_POST['name'])) {
                $dbItem->Name = filter_var($_POST['name']);
                $dbItem->save();
                $result = array('status' => 1);
            }

        }

        return $result;
    }

    /**
     * Delete controller action
     *@var string $PhotoID Item db identifier
     */
    public function __async_delete($PhotoID)
    {
        $result = array('status' => 0);

        /** @var \samson\activerecord\gallery $dbItem */
        $dbItem = null;
        if (dbQuery('gallery')->id($PhotoID)->first($dbItem)) {
            // Delete uploaded file
            unlink($dbItem->Src);
            // Delete DB record about this file
            $dbItem->delete();
            $result['status'] = 1;
        }

        return $result;

    }

    public function __async_upload()
    {
        // Create AJAX response array
        $result = array('status' => 0);
        // Create an empty SQL query
        $dbItem = new \samson\activerecord\gallery(false);

        // Create object for uploading file to server
        $upload = new \samsonphp\upload\Upload(array('png','jpg', 'jpeg', 'gif'));

        if ($upload->upload($filePath, $fileName, $realName)) {
            // Store the path to the uploaded file in the DB
            $dbItem->Src = $filePath;
            // Save file size to the DB
            $dbItem->size = $upload->size();
            // Save the original name of the picture in the DB
            $dbItem->Name = $realName;
            // Execute the query
            $dbItem->save();
            // Change result status for successful asynchronous action
            $result['status'] = 1;
        }

        return $result;
    }
}
```

Now we have to move all vies from ```app/view/gallery``` to the ```src/gallery/www``` folder. All this views suppose to have ```.vphp``` extensions. And we have to remove ```gallery/``` from every call of views in the methods. And replace ```m()->``` to the ```$this->```.

##Buil Image and Collection entities

We wan to build OOP module so we have to create ```src/Image.php```. 
```php
<?php

namespace gallery;

class Image extends \samson\activerecord\gallery
{

}
```
We will add some cool features to this entity later.

### Collection

We have to separate our module which will work with DB from controller which will interact with user. So let's create ```src/Collection.php``` to get the image collection from database.
```php
<?php

namespace gallery;


use samsonos\cms\collection\Generic;
use samson\pager\Pager;

class Collection extends Generic
{
    /** @var  string Sorter name */
    protected $sorter;

    /** @var  string Sorter direction */
    protected $direction;

    /** @var  int Current page for pager */
    protected $currentPage;

    /** @var  int Number of items per page for pager */
    protected $pageSize;

    /** @var string Block view file */
    protected $indexView = 'www/index';

    /** @var string Item view file */
    protected $itemView = 'www/item';

    /** @var  \samson\pager\Pager Pagination */
    public $pager;

    public $collection = array();

    /**
     * Fill collection with items
     * @return array Collection of items
     */
    public function fill()
    {
        // Prepare db query object
        $query = dbQuery('gallery\Image');

        // Set the sorting and limit condition to db request
        $query->order_by($this->sorter, $this->direction)->limit($this->pager->start, $this->pager->end);


        // Iterate all records from "gallery" table

        foreach ($query->exec() as $dbItem) {

            /*
             * Render view(output method) and pass object received from DB and
             * prefix all its fields with "image_", return and gather this outputs
             * in $items
             */
            $this->collection[] = $dbItem;
        }
        return $this->collection;
    }

    /**
     * Generic collection constructor
     * @var \samson\core\IViewable View render object
     */
    public function __construct($renderer, $sorter, $direction, $currentPage, $pageSize)
    {
        parent::__construct($renderer);

        $this->sorter = $sorter;
        $this->direction = $direction;
        $this->currentPage = $currentPage;
        $this->pageSize = $pageSize;

        if (!isset($this->pager)) {
            // Set the prefix for pager
            $urlPrefix = "gallery/list/" . $this->sorter . "/" . $this->direction . "/";
            // Count the number of images in query
            $query = dbQuery('gallery\Image');
            $rowsCount = $query->count();

            // Create a new instance of Pager
            $this->pager = new Pager($this->currentPage, $this->pageSize, $urlPrefix, $rowsCount);
        }
        $this->fill();
    }
}
```

Now we have to modify our ```list``` action in ```src/Controller.php``` to use new entities.
```php
 /** Gallery images list asynchronous controller action */
    public function __async_list($sorter = null, $direction = 'ASC', $currentPage = 1, $pageSize = 4)
    {
        // Set the $result['status'] to 1 to provide asynchronous functionality
        $result = array('status' => 1);

        // If no sorter is passed
        if (!isset($sorter)) {
            // Load sorter from session if it is there
            $sorter = isset($_SESSION['sorter']) ? $_SESSION['sorter'] : null;
            $direction = isset($_SESSION['direction']) ? $_SESSION['direction'] : null;
        }
        // If no page is passed
        if (!isset($currentPage)) {
            // Load current page from session if it is there
            $currentPage = isset($_SESSION['SamsonPager_current_page']) ? $_SESSION['SamsonPager_current_page'] : 1;
        }

        if (isset($sorter) && in_array($sorter, array('Loaded', 'size'))) {
            // Store sorting in a session
            $_SESSION['sorter'] = $sorter;
            $_SESSION['direction'] = $direction;
        }

        /** @var  Collection  */
        $collection = new Collection($this, $sorter, $direction, $currentPage, $pageSize);

        $pager = $collection->pager;

        $items = '';
        foreach ($collection->collection as $dbItem) {
            /**@var \samson\activerecord\gallery $dbItem``` */

            /*
             * Render view(output method) and pass object received from DB and
             * prefix all its fields with "image_", return and gather this outputs
             * in $items
             */
            $items .= $this->view('item')->image($dbItem)->sorter($sorter)->direction($direction)->current_page($currentPage)->output();
        }

        // Include the data about images in the result array
        $result['list'] = $this->view('list')->items($items)->output();
        // Include the data about Pager state in the result array
        $result['pager'] = $pager->toHTML();
        // Include the data about sorter links state in the result array
        $result['sorter'] = $this->view('sorter')->current_page($currentPage)->output();

        return $result;
    }
 ```

### Image

We have to remove all the functionality from ```Controller.php``` and create methods in ```Image.php``` entity which will provide same functionality. This how we will separate module and controller functions.
First of all we have to create static method which will find DB record by id.
```php
namespace gallery;

class Image extends \samson\activerecord\gallery
{
    /**
     * @param $photoID
     * @return Image
     */
    public static function byID($photoID)
    {
        return dbQuery('gallery\Image')->id($photoID)->first();
    }
}
```
Now we can find a record in the database without creation of Image instance. This function returns as an instance of Image in case in find the record with passed id and false if there is no record with such id.
Now we can replace every ```if (dbQuery('gallery')->id($id)->first($dbItem))``` statement with ```if (false != ($dbItem = Image::byID($photoID)))```, so we call external method instead of execution of the query in the controller. So our ```__async_form``` controller will be changed to:
```php
    /**
     * Gallery form controller action
     * @var string $photoID Item identifier
     * @return int status for asynchronous action
     */
    public function __async_form($photoID = null)
    {
        $result = array('status' => 1);
        // Try to recieve one first record from DB by identifier,
        if (false != ($dbItem = Image::byID($photoID))) {
            // Render the form to redact item
            $result['form'] = $this->view('/form/index')->title('Redact form')->image($dbItem)->form($this->view('/form/newfile')->output())->output();
        } elseif (isset($photoID)) {
            // File with passed ID wasn't find in DB
            $result['form'] = $this->view('/form/notfoundID')->title('Not Found')->output();
        } else {
            // No ID was passed
            $result['form'] = $this->view('/form/newfile')->image_PhotoID(0)->title('New Photo')->output();
        }
        return $result;
    }
```
As we get installed ```samsonphp/upload``` module and using it for uploading the images we have to modify our ```newfile.vphp``` view and add ```PhotoID``` to the action, so we could track the id passed to the controller.
```php
<div class="upload_form">
    <p>
        <input type="hidden" class="__action" value="<?php url_base('gallery', 'upload', 'image_PhotoID', 'list', 'Loaded', 'DESC', '1'); ?>/">
        <input type="hidden" class="__file_size" value="50000000">
        <input class="__upload" type="file" name="uploadFile">
    </p>
</div>
```

Image extends ```\samson\activerecord\``` so it already have ```delete``` and save methods. We have to rebuild this methods to provide required functionality. Our ```delete``` method suppose do delete file from server and remove record about image in database. But if we want only to replace image we have to delete file from server.
```php
    /**
     * @param bool $full if false delete only file from server
     */
    public function delete($full = true)
    {
        /**@var \samsonphp\fs\FileService $fs Pointer to file service */
        $fsys = & m('fs');

        // Get the real path to the image
        $imgSrc = realpath(getcwd().$this->Src);

        // If file exist delete this file from sever
        if ($fsys->exists($imgSrc)) {

            $fsys->delete($imgSrc);
            if ($full) {
                // Delete DB record about this file
                parent::delete();
            }
        }
    }
```
So we have to rebuild our ```__async_delete``` controller in ```Controller.php```.
```php
    /**
     * Delete controller action
     * @var string $PhotoID Item db identifier
     * @return int status for asynchronous action
     */
    public function __async_delete($photoID)
    {
        $result = array('status' => 0);
        /** @var \gallery\Image $dbItem */
        if (false != ($dbItem = Image::byID($photoID))) {
            // Delete image from server and remove DB record about this image
            $dbItem->delete();
            // Change the request status for successful asynchronous action
            $result['status'] = 1;
        }
        return $result;
    }
```

Now let's build our ```save``` method in ```Image.php``` so it could upload image to the server and place the record to the DB about this image.
```php
    /**
     * @param \samsonphp\upload\Upload $upload
     * @return bool|void
     */
    public function save(\samsonphp\upload\Upload $upload = null)
    {
        $result = false;
        // If upload is successful return true
        if (isset($upload) && $upload->upload($filePath, $fileName, $realName)) {
            // Store the path to the uploaded file in the DB
            $this->Src = $filePath;

            // Save file size to the DB
            $this->size = $upload->size();

            // Save the original name of the picture in the DB for new image or leave old name
            $this->Name = empty($this->Name) ? $realName : $this->Name;

            $result = true;
        }

        // Execute the query
        parent::save();

        return $result;
    }
```
Now we have to rebuild our ```__async_upload``` controller in ```Controller.php``` to use this method instead of db requests.
```php
    /**
     * Upload controller action
     * @return int status for asynchronous action
     */
    public function __async_upload($photoID = null)
    {
        // Create AJAX response array
        $result = array('status' => 0);
        
        /*
         * Try to receive one first record from DB by identifier,
         * in case of success store record into $dbItem variable,
         * delete old picture from server without deleting DB record.
         * Otherwise create new instance of \gallery\Image
         */
        if (false != ($dbItem = Image::byID($photoID))) {
            $dbItem->delete(false);
        } else {
            /** @var \gallery\Image $dbItem */
            $dbItem = new Image(false);
        }
        /*
         * Upload file to the server, in case of success
         * set the request status to 1 for successful asynchronous action
         */
        if ($dbItem->save(new \samsonphp\upload\Upload(array('png', 'jpg', 'jpeg', 'gif')))) {

            $result['status'] = 1;
        }
        return $result;
    }
```

Let's create ```updateName``` method for ```__async_save``` controller. This controller now response only for image name.
```php
    public function updateName($name)
    {
        $this->Name = $name;

        // Execute the query
        parent::save();
    }
```
And our ```__async_save``` controller now looks like:
```php
    /**
     * Gallery save controller action
     * @var string $PhotoID Item identifier
     * @return int status for asynchronous action and view
     */
    public function __async_save()
    {
        $result = array('status' => 0);
        // If we have really received form data
        if (isset($_POST)) {
            // Clear received variable
            $photoID = isset($_POST['id']) ? filter_var($_POST['id']) : null;
            /*
             * Try to receive one first record from DB by identifier,
             * in case of success store record into $dbItem variable.
             */
            if (false != ($dbItem = Image::byID($photoID))) {
                // Update image name in DB
                $dbItem->updateName(filter_var($_POST['name']));
                // Change the request status for successful asynchronous action
                $result = array('status' => 1);
            }
        }
        return $result;
    }
```