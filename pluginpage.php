<?php

// This is a simple plugin page showcasing how strings can be processed through the 
// EM framework i18n features.


// Let's use a shortcut to the EM Framework object to save some typing.
$fw = $module->framework;


// Set the language -- this can be removed as soon as the minimal REDCap version 
// supported by this module is 9.5.0 or greater.
$module->set_language($fw->getProjectId());


// Let's get some data from the current project, like the number of records.
// This is only to have something to show, and therefore a need for some labels,
// which of course should be translatable.
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
$js = $fw->getUrl("js/fun.js");

// Generate the page content. This is rather boring, except that we use tt() to
// output our strings.
?>

<script src="<?=$js?>"></script>
<h3><?=$fw->tt("module_name")?></h3>
<p><?=$fw->tt("module_desc")?></p>
<h5><?=$fw->tt("info_header")?></h5>
<ul>
    <li><?=$fw->tt("record_id", $record_id_field)?></li>
    <li><?=$fw->tt($instruments_info_key, $n_instruments, $n_fields)?></li>
</ul>

<?php 
// Only show the fun part if the number set via config is greater than zero.
if ($count > 0) {
    ?>
    <p><b><?=$fw->tt("fun_title")?></b></p>
    <button class="btn btn-primary btn-lg" onclick="doTheFunnyCountingInTheConsole()"><i class="far fa-smile"></i></button>
    <p><?=$fw->tt("fun_explained", $count)?></p>
    <?php 

    // As we want to showcase shuffling data from PHP to JavaScript, we
    // involve the JavaScriptModuleObject.
    // It is important to call this via the framework object when using the polyfill.
    $fw->initializeJavascriptModuleObject();

    // To be extra clever, we do the complicated math in PHP and use JS
    // only to output the results.
    $arr = array();
    for ($i = 1; $i <= $count; $i++) {
        array_push($arr, $i);
    }
    // Now make the results of this heavy lifting available via the JMO:
    $fw->tt_addToJavascriptModuleObject("array", $arr);
    $fw->tt_transferToJavascriptModuleObject("countup", $count);
}