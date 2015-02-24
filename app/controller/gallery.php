<?php

/** Gallery images list controller action */
function gallery_list($sorter = null, $direction = 'ASC', $currentPage = 1, $pageSize=4)
{

    $gallery = gallery_async_list($sorter, $direction, $currentPage, $pageSize);

    /* Set window title and view to render, pass items variable to view, pass the Pager and current page to view*/
    m()->view('gallery/index')->title('My gallery')->gallery_list($gallery['list'])->gallery_sorter($gallery['sorter'])->pager_html($gallery['pager']);
}

/** Gallery universal controller */
function gallery__HANDLER()
{
    // Call our lsit controller
    gallery_list();
}

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
        $items .= m()->view('gallery/item')->image($dbItem)->sorter($sorter)->direction($direction)->current_page($currentPage)->output();
    }

    // Include the data about images in the result array
    $result['list'] = m()->view('gallery/list')->items($items)->output();
    // Include the data about Pager state in the result array
    $result['pager'] = $pager->toHTML();
    // Include the data about sorter links state in the result array
    $result['sorter'] = m()->view('gallery/sorter')->current_page($currentPage)->output();

    return $result;
}

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

/**
 * Delete controller action
 *@var string $id Item db identifier
 */
function gallery_async_delete($id)
{
    $result = array('status' => 0);

    /** @var \samson\activerecord\gallery $dbItem */
    $dbItem = null;
    if (dbQuery('gallery')->id($id)->first($dbItem)) {
        // Delete uploaded file
        unlink($dbItem->Src);
        // Delete DB record about this file
        $dbItem->delete();
        $result['status'] = 1;
    }

    return $result;

}
