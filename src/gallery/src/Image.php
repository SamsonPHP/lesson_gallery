<?php

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

    public function updateName($name)
    {
        $this->Name = $name;

        // Execute the query
        parent::save();
    }
}
