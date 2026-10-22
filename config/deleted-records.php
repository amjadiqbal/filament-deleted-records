<?php

return [
    /*
     * Record who deleted each model in the `deleted_by_id` column added by this package's migration.
     * When the column does not exist yet, nothing is recorded and the "Deleted by" column and filter stay hidden.
     */
    'track_deleted_by' => true,

    /*
     * The user model behind `deleted_by_id`. Null uses the model of the default auth guard's provider.
     */
    'user_model' => null,
];
