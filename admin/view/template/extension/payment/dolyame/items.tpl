    <div class="modal-dialog">
        <div class="modal-content">

            <!--заголовок и кнопка закрытия-->
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title"></h4>
            </div>

            <!--контентная часть окна-->
            <div class="modal-body">
<form method="post" action="index.php?route=extension/payment/dolyame/transactions/refund&token=<?php echo $user_token; ?>" enctype="multipart/form-data" id="form-dolyame-refund">
  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <td style="width: 1px;" class="text-center"><input checked="checked" type="checkbox" onclick="$('input[name*=\'selected\']').prop('checked', this.checked);" /></td>
          <td><?php echo $column_transaction_name; ?></td>
          <td><?php echo $column_transaction_quantity; ?></td>
          <td><?php echo $column_transaction_price; ?></td>
        </tr>
      </thead>
      <tbody>
        <?php foreach($items as $i => $item):?>
          <tr>
            <td><input class="dolyame_payment_return_position" type="checkbox" name="selected[]" value="<?php echo $i;?>" checked="checked" /></td>
            <td><input type="hidden" name="name[]" value="<?php echo htmlspecialchars($item['name']); ?>"><?php echo htmlspecialchars($item['name']); ?></td>
            <td><input id="dolyame_payment_return_quantity_<?php echo $i;?>" type='text' name="quantity[]" value="<?php echo $item['quantity']; ?>" class="form-control text-right" ></td>
            <td><input id="dolyame_payment_return_price_<?php echo $i;?>" type="text" name="price[]" value="<?php echo $item['price']; ?>" class="form-control text-right">
              <input  type="hidden" name="sku[]" value="<?php echo isset($item['sku'])?$item['sku']:''; ?>">
            </td>
          </tr>
        <?php endforeach;?>
        <tr>
          <td colspan="3" class="text-right"><b><?php echo $text_prepaid_amount; ?></b></td>
          <td><input type="text" name="refunded_prepaid_amount" value="<?php echo $prepaid; ?>"  class="form-control"></td>
        </tr>
        <tr>
          <td colspan="3" class="text-right"><b><?php echo $text_total_sum; ?></b></td>
          <td><span id="dolyame_total_refund_sum">0</span></td>
        </tr>
      </tbody>
    </table>
  </div>
  <button type="button" class="btn btn-info dolyame-refund-submit"><?php echo $text_return_action; ?></button>
  <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
</form>
</div>

        </div>
    </div>