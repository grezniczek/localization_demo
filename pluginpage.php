<?php

// Plugin page showcasing module translation.


// Let's get some data from the current project, like the
// number of records.

$record_id_field = REDCap::getRecordIdField();
$instruments = REDCap::getInstrumentNames();
$n_instruments = count($instruments);
$fields = REDCap::getFieldNames();
$n_fields = count($fields);
$instruments_info_key = $n_instruments == 1 ? 
    "num_instruments_singular" :
    "num_instruments_plural";

// Get count from config settings. Make sure it's positive.
$count = $module->getProjectSetting("count");
if ($count == null || !is_numeric($count)) $count = 0;
$count = abs($count);

// Generate the url for the JavaScript file.
$js = $module->framework->getUrl("js/fun.js");

// Generate page content:
?>

<script src="<?=$js?>"></script>
<h3><?=$module->tt("module_name")?></h3>
<p><?=$module->tt("module_desc")?></p>
<h5><?=$module->tt("info_header")?></h5>
<ul>
    <li><?=$module->tt("record_id", $record_id_field)?></li>
    <li><?=$module->tt($instruments_info_key, $n_instruments, $n_fields)?></li>
</ul>

<?php 
// Only show the fun part if the number set via config is greater than zero.
if ($count > 0) {
    ?>
    <p><b><?=$module->tt("fun_title")?></b></p>
    <button class="btn btn-primary btn-lg" onclick="doTheFunnyCountingInTheConsole()"><i class="far fa-smile"></i></button>
    <p><?=$module->tt("fun_explained", $count)?></p>
    <?php 

    // As we want to showcase shuffling data from PHP to JavaScript, we
    // involve the JavaScriptModuleObject.
    $module->initializeJavascriptModuleObject();

    // To be extra clever, we do the complicated math in PHP and use JS
    // only to output the results.
    $arr = array();
    for ($i = 1; $i <= $count; $i++) {
        array_push($arr, $i);
    }
    // Now make the results of this heavy lifting available via the JMO:
    $module->tt_addToJavascriptModuleObject("array", $arr);
    $module->tt_transferToJavascriptModuleObject("countup", $count);
}