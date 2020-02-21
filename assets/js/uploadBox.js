import '../css/uploadBox.scss';

import $ from 'jquery';

$('.custom-file-input').on('change',function(){
    var fileName = $(this).val();
    $(this).next('.custom-file-label').addClass("selected").html(fileName);
});
