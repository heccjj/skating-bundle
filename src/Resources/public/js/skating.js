
$(document).ready(function () {
    //随机slug
    $("[id$='node_slug_random']").click(function(){
        $.ajax({
            url: url_getnodename
        }).done(function(msg) {
            $("[id$='node_slug']").val(msg);
        }); 
    });
    
    //标题slug
    $("[id$='node_slug_title']").click(function(){
        var title = $("[id$='node_title']").val();
        $.ajax({
            method: "POST",
            url: url_getnodename,
            data: { title: title }
        }).done(function(msg) {
            $("[id$='node_slug']").val(msg);
        });        
    });

    //初始slug
    var title = $("[id$='node_title']").val();
    if(title == ''){
        $.ajax({
            method: "POST",
            url: url_getnodename,
            data: { title: title }
        }).done(function(msg) {
            $("[id$='node_slug']").val(msg);
        });
    }

    //上传文件控件初始化
    bsCustomFileInput.init();
    $("[id$='node_file_uploadfile']").change(function(){
        var title = $("[id$='node_file_uploadfile']").val();
        var pos = title.lastIndexOf("\\");
        title = title.substring(pos+1);
        
        if($("[id$='node_title']").val() == ''){
            $("[id$='node_title']").val(title);
        }
    });

  });

    //////////////
    //tags based on select2
    function select2InputTags(queryStr) {
        var $input = $(queryStr)

        var $select = $('<select class="'+ $input.attr('class') + '" multiple="multiple"><select>')
        if ($input.val() != "") {
            $input.val().split(',').forEach(function(item) {
            $select.append('<option value="' + item + '" selected="selected">' + item + '</option>')
            });
        }
        $select.insertAfter($input)
        $input.hide()

        $select.change(function() {
            $input.val($select.val().join(","));
        });

        return $select;
    }
    
    //var $element = $('#node_file_node_tagsArray').select2({
    var $element = select2InputTags('input[name$="[tagsText]"').select2({
        tags: true,
        tokenSeparators: [',', ' '],
        placeholder: {
            id: "-1",
            placeholder: "选择Tags"
        }
    });

    var $request = $.ajax({
        //url: '{{ path("tags") }}'
        url:  $('input[name$="[tagsText]"').data('ajax'),
    });
    
    $request.then(function (data) {
        for (var d = 0; d < data.length; d++) {
            var item = data[d];
            var option = new Option(item.text, item.id, false, false);
            $element.append(option);
        }
        $element.trigger('change');
    });

  
    /////////////
    //metas:
    var $collectionHolder;

    var $addTagButton = $('<button type="button" class="add_tag_link btn btn-primary">添加元信息</button>');
    var $newLinkLi = $('<li class="list-group-item"></li>').append($addTagButton);

    $(document).ready(function() {
        $collectionHolder = $("[id$='_node_metas']");  // $('#node_news_node_metas');
        $collectionHolder.append($newLinkLi);
        $collectionHolder.data('index', $collectionHolder.find('input').length);

        $addTagButton.on('click', function(e) {
            addTagForm($collectionHolder, $newLinkLi);
        });
    });

    function addTagForm($collectionHolder, $newLinkLi) {
        var prototype = $collectionHolder.data('prototype');
        var index = $collectionHolder.data('index');

        var newForm = prototype;
        newForm = newForm.replace(/__name__/g, index);

        $collectionHolder.data('index', index + 1);
        var $newFormLi = $('<li></li>').append(newForm);
        $newLinkLi.before($newFormLi);
    }
    /////////////
        
//////////