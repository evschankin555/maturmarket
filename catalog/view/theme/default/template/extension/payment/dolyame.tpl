<div id="payment">
<div class="buttons">
  <div class="pull-right">
    <input type="button" value="<?php echo $button_confirm; ?>" id="button-confirm" data-loading-text="<?php echo $text_loading; ?>" class="btn btn-primary" />
  </div>
</div>
</div>
<script type="text/javascript"><!--
$('#button-confirm').bind('click', function() {
	$.ajax({
		data:{},
		type: 'GET',
		url: 'index.php?route=extension/payment/dolyame/send',
		dataType:'json',
		cache: false,
		beforeSend: function() {
			$('#button-confirm').button('loading');
			$('.alert').remove();
		},
		complete: function() {
			$('#button-confirm').button('reset');
		},
		success: function(data) {
			if(data.error){
				$('.alert').remove();
				$('#payment').before('<div class="alert alert-danger">' + data.error + '</div>');
			} else {
				document.location = data.link;
			}
		},
		error: function() {
			$('.alert').remove();
			$('#payment').before('<div id="comepay_error" class="alert alert-danger">Произошла ошибка, попробуйте ещё раз</div>');
		},
	});
});
//--></script>