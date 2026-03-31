<?php

return [
    'navigation' => [
        'title' => 'Work Centers',
        'group' => 'Configuration',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title'  => 'General',
                'fields' => [
                    'name'             => 'Name',
                    'name-placeholder' => 'eg. Assembly Line 1',
                    'code'             => 'Code',
                    'code-placeholder' => 'eg. AL1',
                    'company'          => 'Company',
                    'calendar'         => 'Working Hours',
                    'note'             => 'Description',
                ],
            ],

            'configuration' => [
                'title'  => 'Configuration',
                'fields' => [
                    'working-state'    => 'Working State',
                    'default-capacity' => 'Default Capacity',
                    'time-efficiency'  => 'Time Efficiency',
                    'oee-target'       => 'OEE Target',
                    'costs-per-hour'   => 'Cost per Hour',
                    'setup-time'       => 'Setup Time',
                    'cleanup-time'     => 'Cleanup Time',
                    'time-suffix'      => 'minutes',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'             => 'Name',
            'code'             => 'Code',
            'company'          => 'Company',
            'calendar'         => 'Working Hours',
            'working-state'    => 'Working State',
            'default-capacity' => 'Capacity',
            'time-efficiency'  => 'Efficiency',
            'costs-per-hour'   => 'Cost per Hour',
            'deleted-at'       => 'Deleted At',
            'created-at'       => 'Created At',
            'updated-at'       => 'Updated At',
        ],

        'groups' => [
            'company' => 'Company',
        ],

        'filters' => [
            'company'       => 'Company',
            'working-state' => 'Working State',
        ],

        'actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Work center restored',
                    'body'  => 'The work center has been restored successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Work center archived',
                    'body'  => 'The work center has been archived successfully.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'Work center deleted',
                        'body'  => 'The work center has been permanently deleted.',
                    ],

                    'error' => [
                        'title' => 'Work center could not be deleted',
                        'body'  => 'The work center cannot be deleted because it is currently in use.',
                    ],
                ],
            ],
        ],

        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Work centers restored',
                    'body'  => 'The selected work centers have been restored successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Work centers archived',
                    'body'  => 'The selected work centers have been archived successfully.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'Work centers deleted',
                        'body'  => 'The selected work centers have been permanently deleted.',
                    ],

                    'error' => [
                        'title' => 'Work centers could not be deleted',
                        'body'  => 'One or more selected work centers are currently in use.',
                    ],
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'General Information',

                'entries' => [
                    'name'     => 'Work Center',
                    'code'     => 'Code',
                    'company'  => 'Company',
                    'calendar' => 'Working Hours',
                    'note'     => 'Description',
                ],
            ],

            'configuration' => [
                'title' => 'Configuration',

                'entries' => [
                    'working-state'    => 'Working State',
                    'default-capacity' => 'Default Capacity',
                    'time-efficiency'  => 'Time Efficiency',
                    'oee-target'       => 'OEE Target',
                    'costs-per-hour'   => 'Cost per Hour',
                    'setup-time'       => 'Setup Time',
                    'cleanup-time'     => 'Cleanup Time',
                    'time-suffix'      => 'minutes',
                ],
            ],

            'record-information' => [
                'title' => 'Record Information',

                'entries' => [
                    'created-by'   => 'Created By',
                    'created-at'   => 'Created At',
                    'last-updated' => 'Last Updated',
                ],
            ],
        ],
    ],
];
