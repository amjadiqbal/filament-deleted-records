<?php

return [
    'resource' => [
        'model_label' => 'deleted record',
        'plural_model_label' => 'deleted records',
        'navigation_label' => 'Deleted records',
        'empty_heading' => 'Nothing has been deleted',
        'empty_description' => 'Records deleted from models that use KeepsDeletedModels appear here, ready to restore.',
    ],
    'columns' => [
        'record' => 'Record',
        'type' => 'Type',
        'key' => 'Key',
        'deleted_by' => 'Deleted by',
        'deleted_at' => 'Deleted',
    ],
    'filters' => [
        'type' => 'Type',
        'deleted_by' => 'Deleted by',
        'deleted_from' => 'Deleted from',
        'deleted_until' => 'Deleted until',
        'deleted_from_indicator' => 'Deleted from :date',
        'deleted_until_indicator' => 'Deleted until :date',
    ],
    'preview' => [
        'heading' => 'Deleted :record',
        'summary' => 'Summary',
        'attributes' => 'Stored attributes',
        'attribute' => 'Attribute',
        'value' => 'Value',
        'status' => 'Restore',
        'status_ready' => 'Ready to restore',
        'status_conflict' => 'A record with this key exists',
        'status_missing_class' => 'Model class not found',
        'masked' => '•••••• (hidden)',
    ],
    'actions' => [
        'restore' => [
            'label' => 'Restore',
            'modal_heading' => 'Restore :record?',
            'modal_description' => 'The record is saved back to its table with the same key and attributes, and removed from this list.',
            'success' => 'Restored :record',
            'open' => 'Open',
            'failed' => 'Could not restore :record',
        ],
        'restore_bulk' => [
            'label' => 'Restore selected',
            'modal_heading' => 'Restore the selected records?',
            'success' => 'Restored :count record|Restored :count records',
            'partial' => 'Restored :restored of :total. :failed could not be restored (a record with the same key may already exist).',
        ],
        'force_delete' => [
            'label' => 'Delete permanently',
            'modal_heading' => 'Delete :record permanently?',
            'modal_description' => 'The stored copy is removed for good. It cannot be restored afterwards.',
            'success' => 'Deleted permanently',
        ],
        'force_delete_bulk' => [
            'label' => 'Delete selected permanently',
            'modal_heading' => 'Delete the selected records permanently?',
            'modal_description' => 'The stored copies are removed for good. They cannot be restored afterwards.',
            'success' => 'Deleted :count record permanently|Deleted :count records permanently',
        ],
    ],
];
