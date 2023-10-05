<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-cod" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
        <?php foreach($breadcrumbs as $breadcrumb):?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <div class="container-fluid">
    <?php if ($error_warning) : ?>
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php endif; ?>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
      </div>
      <div class="panel-body">
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-cod" class="form-horizontal">

          <div class="form-group required">
            <label class="col-sm-2 control-label" for="input-paymentname"><?php echo $entry_paymentname; ?></label>
            <div class="col-sm-10">
              <input type="text" name="dolyame_paymentname" value="<?php echo $dolyame_paymentname; ?>" placeholder="<?php echo $entry_paymentname; ?>" id="input-paymentname" class="form-control" />
              <?php if ($error_paymentname): ?>
              <div class="text-danger"><?php echo $error_paymentname; ?></div>
              <?php endif; ?>
            </div>
          </div>
          <div class="form-group required">
            <label class="col-sm-2 control-label" for="input-login"><?php echo $entry_login; ?></label>
            <div class="col-sm-10">
              <input type="text" name="dolyame_login" value="<?php echo $dolyame_login; ?>" placeholder="<?php echo $entry_login; ?>" id="input-login" class="form-control" />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-password"><?php echo $entry_password; ?></label>
            <div class="col-sm-10">
              <input type="text" name="dolyame_password" value="<?php echo $dolyame_password; ?>" placeholder="<?php echo $entry_password; ?>" id="input-password" class="form-control" />
            </div>
          </div>

          <div class="form-group required">
            <label class="col-sm-2 control-label" for="input-cert_path"><span data-toggle="tooltip" title="<?php echo $help_cert_path; ?>"><?php echo $entry_cert_path; ?></span></label>
            <div class="col-sm-10">
              <input type="text" name="dolyame_cert_path" value="<?php echo $dolyame_cert_path; ?>" placeholder="<?php echo $entry_cert_path; ?>" id="input-cert_path" class="form-control" />
              <?php if ($error_cert_path) : ?>
              <div class="text-danger"><?php echo $error_cert_path; ?></div>
              <?php endif; ?>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-key_path"><span data-toggle="tooltip" title="<?php echo $help_key_path; ?>"><?php echo $entry_key_path; ?></span></label>
            <div class="col-sm-10">
              <input type="text" name="dolyame_key_path" value="<?php echo $dolyame_key_path; ?>" placeholder="<?php echo $entry_key_path; ?>" id="input-key_path" class="form-control" />
              <?php if ($error_key_path) : ?>
              <div class="text-danger"><?php echo $error_key_path; ?></div>
              <?php endif; ?>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-prefix"><?php echo $entry_prefix; ?></label>
            <div class="col-sm-10">
              <input type="text" name="dolyame_prefix" value="<?php echo $dolyame_prefix; ?>" placeholder="<?php echo $entry_prefix; ?>" id="input-prefix" class="form-control" />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-order-status"><?php echo $entry_order_status; ?></label>
            <div class="col-sm-10">
              <select name="dolyame_order_status_id" id="input-order-status" class="form-control">
                <?php foreach($order_statuses as $order_status): ?>
                <?php if ($order_status['order_status_id'] == $dolyame_order_status_id) : ?>
                <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                <?php else : ?>
                <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                <?php endif; ?>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-order-refund-status"><?php echo $entry_order_refund_status; ?></label>
            <div class="col-sm-10">
              <select name="dolyame_order_refund_status_id" id="input-order-refund-status" class="form-control">
                <?php foreach($order_statuses as $order_status): ?>
                <?php if ($order_status['order_status_id'] == $dolyame_order_refund_status_id) : ?>
                <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                <?php else : ?>
                <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                <?php endif; ?>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-geo-zone"><?php echo $entry_geo_zone; ?></label>
            <div class="col-sm-10">
              <select name="dolyame_geo_zone_id" id="input-geo-zone" class="form-control">
                <option value="0"><?php echo $text_all_zones; ?></option>
                <?php foreach ($geo_zones as $geo_zone) : ?>
                <?php if ($geo_zone['geo_zone_id'] == $dolyame_geo_zone_id) : ?>
                <option value="<?php echo $geo_zone['geo_zone_id']; ?>" selected="selected"><?php echo $geo_zone['name']; ?></option>
                <?php else : ?>
                <option value="<?php echo $geo_zone['geo_zone_id']; ?>"><?php echo $geo_zone['name']; ?></option>
                <?php endif; ?>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-status"><?php echo $entry_status; ?></label>
            <div class="col-sm-10">
              <select name="dolyame_status" id="input-status" class="form-control">
                <?php if ($dolyame_status) : ?>
                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                <option value="0"><?php echo $text_disabled; ?></option>
                <?php else : ?>
                <option value="1"><?php echo $text_enabled; ?></option>
                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                <?php endif; ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-sort-order"><?php echo $entry_sort_order; ?></label>
            <div class="col-sm-10">
              <input type="text" name="dolyame_sort_order" value="<?php echo $dolyame_sort_order; ?>" placeholder="<?php echo $entry_sort_order; ?>" id="input-sort-order" class="form-control" />
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php echo $footer; ?>