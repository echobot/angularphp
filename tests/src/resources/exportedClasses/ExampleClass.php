<?php

class ExampleClass
{
    /** @Export @Id */
    public $idField;

    /** @Export */
    public $exportedProperty1 = 'exportedProperty1 value';

    /** @Export */
    public $exportedProperty2 = 'exportedProperty2 value';

    public $nonExportedField = 'Non-exported field value';

    /** @Export */
    public function exportedMethod()
    {
        return true;
    }

    public function nonExportedMethod()
    {
        return true;
    }
}
