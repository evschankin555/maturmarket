<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
        <?php foreach($breadcrumbs as $breadcrumb): ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <div class="container-fluid">
    <?php if ($error_warning): ?>
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?php echo $success; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php endif ?>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-list"></i> <?php echo $text_list; ?></h3>
      </div>
      <div class="panel-body">
        <div class="well">
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label class="control-label" for="input-order-id"><?php echo $entry_order_id; ?></label>
                <input type="text" name="filter_order_id" value="<?php echo $filter_order_id; ?>" placeholder="<?php echo $entry_order_id; ?>" id="input-order-id" class="form-control" />
              </div>
            </div>
            <div class="col-sm-8">
              <div class="form-group">
              <button type="button" id="button-filter" class="btn btn-primary pull-right"><i class="fa fa-filter"></i> <?php echo $button_filter; ?></button>
            </div>
          </div>
            </div>
          </div>
          <form method="post" action="" enctype="multipart/form-data" id="form-order">
          <div class="table-responsive">
            <table class="table table-bordered table-hover">
              <thead>
                <tr>
                  <td class="text-right">
                    <?php if ($sort == 'o.order_id'): ?>
                    <a href="<?php echo $sort_order; ?>" class="<?php echo strtolower($order); ?>"><?php echo $column_order_id; ?></a>
                    <?php else: ?>
                    <a href="<?php echo $sort_order; ?>"><?php echo $column_order_id; ?></a>
                    <?php endif ?></td>
                  <td class="text-left">
                    <?php if ($sort == 'o.status'): ?>
                    <a href="<?php echo $sort_status; ?>" class="<?php echo strtolower($order); ?>"><?php echo $column_status; ?></a>
                    <?php else: ?>
                    <a href="<?php echo $sort_status; ?>"><?php echo $column_status; ?></a>
                    <?php endif ?></td>
                  <td class="text-right">
                    <?php if ($sort == 'o.amount'): ?>
                    <a href="<?php echo $sort_total; ?>" class="<?php echo strtolower($order); ?>"><?php echo $column_total; ?></a>
                    <?php else: ?>
                    <a href="<?php echo $sort_total; ?>"><?php echo $column_total; ?></a>
                    <?php endif ?></td>
                  <td class="text-right"><?php echo $column_action; ?></td>
                </tr>
              </thead>
              <tbody>
                <?php if ($transactions): ?>
                <?php foreach($transactions as $transaction): ?>
                <tr>
                  <td class="text-right"><?php echo $transaction['order_id']; ?></td>
                  <td class="text-left"><?php echo $transaction['status']; ?></td>
                  <td class="text-right"><?php echo $transaction['amount']; ?></td>
                  <td class="text-right">
                    <?php if (!empty($transaction['refund'])): ?>
                    <button type="button" id="doyame-refund-id-<?php echo $transaction['order_id']; ?>" class="btn btn-info" onclick="showDolyameRefundForm(<?php echo $transaction['order_id']; ?>)"><?php echo $text_refund_action; ?></button>
                    <?php endif ?>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr>
                  <td class="text-center" colspan="8"><?php echo $text_no_results; ?></td>
                </tr>
                <?php endif;  ?>
              </tbody>
            </table>
          </div>
        </form>
        <div class="row">
          <div class="col-sm-6 text-left"><?php echo $pagination; ?></div>
          <div class="col-sm-6 text-right"><?php echo $results; ?></div>
        </div>
        </div>
      </div>
    </div>
  </div>
  <script type="text/javascript"><!--
$('#button-filter').on('click', function() {
	url = 'index.php?route=extension/payment/dolyame/transactions&token=<?php echo $user_token; ?>';

	var filter_order_id = $('input[name=\'filter_order_id\']').val();

	if (filter_order_id) {
		url += '&filter_order_id=' + encodeURIComponent(filter_order_id);
	}

	location = url;
});
function setOrderStatus(data) {
  $.ajax({
    url: '<?php echo $catalog; ?>index.php?route=api/order/history&token=' + token + '&order_id=' + data.order_id,
    type: 'post',
    dataType: 'json',
    data: 'order_status_id=<?php echo $refund_status ?>&notify=1&override=0&append=0&comment=' + data.comment,
    success: function(json) {
      if (json['error']) {
        alert(json['error']);
      }
      location.reload();
    },
    error: function(xhr, ajaxOptions, thrownError) {
      alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
    }
  });
}
$(document).on('click', '.dolyame-refund-submit', function(){
  $('.dolyame-refund-submit').button('loading');
  var form = $('#form-dolyame-refund');
  var actionUrl = form.attr('action');
    $.ajax({
        type: "POST",
        dataType: 'json',
        url: actionUrl,
        data: form.serialize(), // serializes the form's elements.
        success: function(data)
        {
          if (typeof data.error !== 'undefined') {
            alert(data.error);
            $('.dolyame-refund-submit').button('reset');
            return;
          } else {
            setOrderStatus(data);
          }
        }
    });
});
$(document).on('change', '.dolyame_payment_return_position', dolyamePaymentUpdateTotal);
$(document).on('keyup', '#dolyame-modal-refund input', dolyamePaymentUpdateTotal);
function dolyamePaymentUpdateTotal(){
  var sum = 0;
  document.querySelectorAll('.dolyame_payment_return_position:checked').forEach(function(el){
    sum += $('#dolyame_payment_return_quantity_' + el.value).val() * $('#dolyame_payment_return_price_' + el.value).val()
  });
  sum -= $('input[name="refunded_prepaid_amount"]').val();
  $('#dolyame_total_refund_sum').text(sum.toFixed(2));
}
function showDolyameRefundForm(id) {
    $.ajax({
        url: 'index.php?route=extension/payment/dolyame/transactions/items&order_id='+id+'&token=<?php echo $user_token; ?>',
        dataType: 'html',
        beforeSend: function() {
            $('#doyame-refund-id-'+id).button('loading');
        },
        complete: function() {
            $('#doyame-refund-id-'+id).button('reset');
        },
        success: function(html) {
            $('#dolyame-modal-refund').remove();

            $('body').prepend('<div id="dolyame-modal-refund" class="modal">' + html + '</div>');

            $('#dolyame-modal-refund').modal('show');
            dolyamePaymentUpdateTotal();
        },
        error: function(xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
    });
}
var token = '';

function getToken()
{
  // Login to the API
$.ajax({
  url: '<?php echo $catalog; ?>index.php?route=api/login',
  type: 'post',
  dataType: 'json',
  data: 'key=<?php echo $api_key; ?>',
  crossDomain: true,
  success: function(json) {
    $('.alert').remove();

        if (json['error']) {
        if (json['error']['key']) {
          $('#content > .container-fluid').prepend('<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> ' + json['error']['key'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
        }

            if (json['error']['ip']) {
          $('#content > .container-fluid').prepend('<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> ' + json['error']['ip'] + ' <button type="button" id="button-ip-add" data-loading-text="<?php echo $text_loading; ?>" class="btn btn-danger btn-xs pull-right"><i class="fa fa-plus"></i> <?php echo $button_ip_add; ?></button></div>');
        }
        }

        if (json['token']) {
      token = json['token'];
    }
  },
  error: function(xhr, ajaxOptions, thrownError) {
    alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
  }
});
}

getToken();


$(document).delegate('#button-ip-add', 'click', function() {
  $.ajax({
    url: 'index.php?route=user/api/addip&token=<?php echo $user_token; ?>&api_id=<?php echo $api_id; ?>',
    type: 'post',
    data: 'ip=<?php echo $api_ip; ?>',
    dataType: 'json',
    beforeSend: function() {
      $('#button-ip-add').button('loading');
    },
    complete: function() {
      $('#button-ip-add').button('reset');
    },
    success: function(json) {
      $('.alert').remove();

      if (json['error']) {
        $('#content > .container-fluid').prepend('<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> ' + json['error'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
      }

      if (json['success']) {
        $('#content > .container-fluid').prepend('<div class="alert alert-success"><i class="fa fa-check-circle"></i> ' + json['success'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
      }
      getToken();
    },
    error: function(xhr, ajaxOptions, thrownError) {
      alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
    }
  });
});

//--></script> 
</div>
<?php echo $footer; ?>