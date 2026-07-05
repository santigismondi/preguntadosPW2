<?php

return [
    'permissions' => [
        'profile.view',
        'post.create',
        'post.edit',
        'post.delete',
        'reports.view',
        'admin.dashboard',
    ],
    'roles' => [
        'Usuario' => [
            'profile.view',
            'reports.view',
        ],
        'Editor' => [
            'profile.view',
            'post.create',
            'post.edit',
            'post.delete',
            'reports.view',
        ],
        'Administrador' => [
            '*',
        ],
    ],
];
