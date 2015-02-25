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
             * Render view(output method) and pass object received fron DB and
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