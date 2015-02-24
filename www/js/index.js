/** Handler will be called when DOM will load and body will be available */

var container = s('.gallery-container');
var pager = s('#pager');
var sorter = s('#line1');

s(document.body).pageInit(function(body){
    s('.btn_update').ajaxClick(function(response){
        console.log(response.status);
        console.log(response.list);
    });
});



var load = function(response)
{
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
}

s('#pager').pageInit(load);
