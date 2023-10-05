<?php
$_['heading_title']               = 'Yandex.Metrica';

// Text
$_['text_extension']              = 'Extensions';
$_['text_success']	              = 'Settings changed';
$_['text_edit']                   = 'Module settings';
$_['text_signup']                 = 'To get started, enter the relevant Yandex.Metrica tag numbers and the company information. The tag number is specified on the page "<a href="https://metrika.yandex.ru/list">My tags</a>" in Yandex.Metrica next to the site address.
You can create a new tag in the Yandex.Metrica interface at <a href="https://metrika.yandex.ru/">https://metrika.yandex.ru/</a>';
$_['text_default']                = 'By default';
$_['text_find_metric']            = 'Find metric';
$_['text_write_logs']             = 'Record logs';
$_['text_logs']                   = 'Logs';
$_['text_start_of_search_metric'] = 'Start of search';
$_['text_end_of_search_metric']   = 'End of search';
$_['text_metrics_found']          = 'The following metrics were found';
$_['text_metrics_not_found']      = 'Metrics not found';
$_['text_counter_number']         = 'Tag number';
$_['text_notify_module_version']  = 'New module version detected';
$_['text_instructions']           = <<<INSTRUCTIONS
<h2>Instructions for configuring the module</h2>
</br>
<h3>"Main" tab.</h3> 
<p>The main settings for the module are here.</p>
<p><b>Status</b>: Enabling/Disabling the module.</p>
<p><b>Metrics</b>: Metric codes are added here, as well as the status of enabling/disabling Session Replay for metrics.</p>
</br>
<p>To add a metric, click "+".</p>
<p>Next, in the "Metric code" column, enter the metric code created on the site <a href="https://metrika.yandex.ru/">https://metrika.yandex.ru/</a></p>
<p>Also on this tab, you can enable and disable Session Replay in the Settings column.</p>
</br>
<h3>"Metric search" tab</h3>
<p>Here you can check whether a Yandex.Metrica tag is installed on your site. To do this, go to the "Metric search" tab and click "Find metric".</p>
</br>
<h3>"Logging" tab</h3>
<p>On this tab, module error logging is enabled and disabled. When this option is enabled, entries about errors that occur will appear here.</p>
</br>
<p>To save the module settings, click the blue "Save" button in the upper-right corner.</p>
INSTRUCTIONS;

// Tabs
$_['tab_metrics']                 = 'Main';
$_['tab_metric_search']           = 'Metric search';
$_['tab_logging']                 = 'Logging';
$_['tab_info']                    = 'Instructions';

// Column
$_['column_metric_code']          = 'Metric code';
$_['column_settings']             = 'Settings';
$_['column_sort_order']           = 'Sort order';
$_['column_name']                 = 'Name';
$_['column_selector']             = 'Selector';


// Entry
$_['entry_code']                  = 'Yandex.Metrica code';
$_['entry_metrics_list']          = 'Metrics';
$_['entry_status']                = 'Status';
$_['entry_webvisor']              = 'Session Replay';
$_['entry_clickmap']              = 'Click Map';
$_['entry_ecommerce']             = 'E-commerce';
$_['entry_tracking_the_hash'] = 'Hash tracking in the address bar';

// Error
$_['error_permission']            = 'You do not have the rights to manage this module';
$_['error_metric_code']           = 'Metric code missing';
$_['error_selector']              = 'Selector missing';
$_['error_selector_name']         = 'Name missing';
$_['error_selector_defined']      = 'This selector has already been defined';
