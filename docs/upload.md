#Upload module

There is a detail manual how to configure this module [here](https://github.com/SamsonPHP/upload).
But let's go true the installation at this particular case.

##Add module to the composer

Include ```"samsonphp/upload":"*",``` string to the ```composer.json``` file.

##Create the controller

We have to build the controller in ```app/controller/gallery.php``` file to provide the adding data about uploaded file into the DB.
```php
function gallery_async_upload()
{
    // Create AJAX response array
    $result = array('status' => 0);
    // Create an empty SQL query
    $dbItem = new \samson\activerecord\gallery(false);

    // Create object for uploading file to server
    $upload = new \samsonphp\upload\Upload(array('png','jpg', 'jpeg'));

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
```
 
##Modify form view

We have to change ```gallery/form/newfile.php``` view in order to support this module.
```php
<div class="upload_form">
    <p>
        <input type="hidden" class="__action" value="<?php url_base('gallery', 'upload', 'list', 'Loaded', 'DESC', '1'); ?>/">
        <input type="hidden" class="__file_size" value="50000000">
        <input class="__upload" type="file" name="uploadFile">
        <div class="__progress_text"></div>
    </p>
</div>
```
Action has to support asynchronous controller chain. So it would have pretty much the same url structure as a form in the previous section.

##Add javascript handler

We have to modify our ```function edit()``` so it would support this module.
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
            
            /* Upload module support start */
            // Cache file input
            var file = s('.__upload');
            // Bind upload event
            uploadFileHandler(file, {
                // Handle event after upload finishing
                response: function (response) {
                    try
                    {
                        // Parse server response
                        response = JSON.parse(response);

                        // If external response handler is passed
                        if( responseHandler ) responseHandler( response, form);
                    }
                    catch(e){s.trace(e.toString())}

                    // Call load function after uploading the file
                    load(response);
                    // Close tinybox
                    tb.close();
                }

            });
            /* Upload module support end */
        }
    });

}
```

