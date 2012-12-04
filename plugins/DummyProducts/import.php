<?php
umask(0);
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

ini_set('display_errors', 1);
ini_set('max_execution_time', 600);

try {
    /** @var $import AvS_FastSimpleImport_Model_Import */
    $import = Mage::getModel('fastsimpleimport/import');
    $import
        ->setPartialIndexing(true)
        ->setBehavior(Mage_ImportExport_Model_Import::BEHAVIOR_APPEND)
        ->processProductImport($data);
} catch (Exception $e) {
    print_r($import->getErrorMessages());
}
?>

