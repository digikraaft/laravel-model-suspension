<?php

return [
    /*
      * The class name of the suspension model that holds all suspensions.
      *
      * The model must be or extend `Digikraaft\ModelSuspension\Suspension`.
      */
    'suspension_model' => Digikraaft\ModelSuspension\Suspension::class,

    /*
     * The name of the column which holds the ID of the model related to the suspensions.
     *
     * Only change this value if you have set a different name in the migration for the suspensions table.
     */
    'model_primary_key_attribute' => 'model_id',

];
