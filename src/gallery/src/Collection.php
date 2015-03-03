<?php

namespace gallery;

use samsonos\cms\collection\Generic;

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
    protected $indexView = 'www/list';

    /** @var string Item view file */
    protected $itemView = 'www/item';

    /** @var  \samson\pager\Pager Pagination */
    public $pager;


    /**
     * Render collection item block
     * @param mixed $item Item to render
     * @return string Rendered collection item block
     */
    public function renderItem($item)
    {
        return $this->renderer
            ->view($this->itemView)
            ->image($item)
            ->sorter($this->sorter)
            ->direction($this->direction)
            ->current_page($this->pager->current_page)
            ->output();
    }

    /**
     * Fill collection with items
     * @return array Collection of items
     */
    public function fill()
    {
        // Prepare db query object
        $query = dbQuery('gallery\Image');

        // Get the number of images in db for Pager
        $this->pager->update($query->count());

        // Set the sorting and limit condition to db request
        $query->order_by($this->sorter, $this->direction)->limit($this->pager->start, $this->pager->end);

        //
        return $this->collection = $query->exec();
    }

    /**
     * Generic collection constructor
     * @var \samson\core\IViewable View render object
     */
    public function __construct($renderer, $sorter, $direction, \samson\pager\Pager $pager)
    {
        parent::__construct($renderer);

        $this->pager = & $pager;

        $this->sorter = $sorter;
        $this->direction = $direction;

        $this->fill();
    }
}