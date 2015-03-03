<?php

// Set the namespace
namespace gallery;

use samson\pager\Pager;

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

        // Set the prefix for pager
        $urlPrefix = "gallery/list/" . $sorter . "/" . $direction . "/";

        // Create a new instance of Pager
        $pager = new Pager($currentPage, $pageSize, $urlPrefix);

        // Create a new instance of Collection
        $collection = new Collection($this, $sorter, $direction, $pager);

        // Include the data about images in the result array
        $result['list'] = $collection->render();
        // Include the data about Pager state in the result array
        $result['pager'] = $collection->pager->toHTML();
        // Include the data about sorter links state in the result array
        $result['sorter'] = $this->view('sorter')->current_page($currentPage)->output();

        return $result;
    }

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
}
