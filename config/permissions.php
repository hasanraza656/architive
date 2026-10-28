<?php

/*
|--------------------------------------------------------------------------
| Team permissions
|--------------------------------------------------------------------------
| Every module has a list of things a team member may be allowed to do. A role is simply a named set of these
| keys ("module.action"). The role editor in Admin > Team > Roles is generated from this file, so adding a new
| permission here (and checking it with `can('module.action')` in a route or view) makes it appear automatically.
|
| The super admin (users.role = admin) always has everything. Staff always can: sign in, edit their own profile,
| open the chats they were added to and work on tasks assigned to them.
*/

return [
    'modules' => [
        'dashboard' => [
            'label' => 'Dashboard',
            'icon' => 'grid',
            'hint' => 'The overview page with numbers and recent activity.',
            'actions' => [
                'view' => 'See the dashboard',
            ],
        ],
        'orders' => [
            'label' => 'Orders and requests',
            'icon' => 'receipt',
            'hint' => 'Customer requests, offers, invoices and deliveries.',
            'actions' => [
                'view' => 'See all orders and requests (including prices)',
                'create' => 'Create orders',
                'edit' => 'Edit orders and offers',
                'send' => 'Send offers and invoices to customers',
                'deliver' => 'Deliver work to customers',
                'complete' => 'Mark orders completed',
                'cancel' => 'Cancel orders and close requests',
                'delete' => 'Delete orders and requests for good',
            ],
        ],
        'chat' => [
            'label' => 'Chats',
            'icon' => 'message',
            'hint' => 'Team members can always use chats they were added to.',
            'actions' => [
                'access_all' => 'Open every customer chat (not only the ones they were added to)',
                'manage_members' => 'Add or remove people in a chat',
            ],
        ],
        'inbox' => [
            'label' => 'Inbox',
            'icon' => 'mail',
            'hint' => 'All messages in one list.',
            'actions' => [
                'view' => 'Use the inbox',
            ],
        ],
        'customers' => [
            'label' => 'Customers',
            'icon' => 'users',
            'hint' => 'Customer accounts and their history.',
            'actions' => [
                'view' => 'See customers',
                'create' => 'Add customers',
                'edit' => 'Edit customers',
                'delete' => 'Delete customers and everything they own',
            ],
        ],
        'stripe' => [
            'label' => 'Invoices',
            'icon' => 'credit-card',
            'hint' => 'Read-only list of the invoices (from Stripe) with their amounts and customers.',
            'actions' => [
                'view' => 'See invoices',
            ],
        ],
        'projects' => [
            'label' => 'Projects',
            'icon' => 'layers',
            'hint' => 'Projects are also visible to people who were added to them.',
            'actions' => [
                'view' => 'See all projects',
                'create' => 'Create projects',
                'edit' => 'Edit projects and their members',
                'delete' => 'Delete projects',
            ],
        ],
        'tasks' => [
            'label' => 'Tasks',
            'icon' => 'check-circle',
            'hint' => 'People can always see and update tasks assigned to them.',
            'actions' => [
                'view' => 'See all tasks',
                'create' => 'Create tasks',
                'edit' => 'Edit any task and assign people',
                'delete' => 'Delete tasks',
            ],
        ],
        'blog' => [
            'label' => 'Blog',
            'icon' => 'file-text',
            'hint' => 'Articles on the public website.',
            'actions' => [
                'view' => 'See the blog posts list',
                'create' => 'Write new posts',
                'edit' => 'Edit posts',
                'publish' => 'Publish and schedule posts',
                'delete' => 'Delete posts',
                'categories' => 'Manage categories',
            ],
        ],
        'team' => [
            'label' => 'Team and roles',
            'icon' => 'shield',
            'hint' => 'Who can sign in and what they can do.',
            'actions' => [
                'view' => 'See the team list',
                'manage_users' => 'Add, edit and switch off team members',
                'manage_roles' => 'Create and edit roles and permissions',
            ],
        ],
    ],

    /*
    | Starter roles created the first time (they can be renamed, changed or deleted like any other role).
    | Also offered as "start from" templates when creating a new role.
    */
    'presets' => [
        'manager' => [
            'name' => 'Manager',
            'description' => 'Runs projects and customer work day to day.',
            'color' => '#6D28D9',
            'permissions' => [
                'dashboard.view', 'orders.view', 'orders.create', 'orders.edit', 'orders.send', 'orders.deliver', 'orders.complete', 'orders.cancel',
                'chat.access_all', 'chat.manage_members', 'inbox.view', 'customers.view', 'customers.create', 'customers.edit',
                'projects.view', 'projects.create', 'projects.edit', 'tasks.view', 'tasks.create', 'tasks.edit', 'tasks.delete', 'team.view',
            ],
        ],
        'support' => [
            'name' => 'Customer support',
            'description' => 'Answers customers and keeps orders moving.',
            'color' => '#0E7490',
            'permissions' => [
                'dashboard.view', 'orders.view', 'chat.access_all', 'inbox.view', 'customers.view', 'tasks.create',
            ],
        ],
        'designer' => [
            'name' => 'Designer / team member',
            'description' => 'Works on assigned tasks and in the chats they are added to.',
            'color' => '#B45309',
            'permissions' => [
                'tasks.create',
            ],
        ],
    ],
];
