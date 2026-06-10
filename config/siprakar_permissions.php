<?php

return [
    'groups' => [
        'dashboard' => [
            'label' => 'Dashboard',
            'permissions' => [
                'dashboard.view' => 'Lihat dashboard',
            ],
        ],
        'program_kerja' => [
            'label' => 'Program Kerja',
            'permissions' => [
                'program_kerja.view' => 'Tampilkan menu & tabel',
                'program_kerja.show' => 'Lihat detail',
                'program_kerja.create' => 'Tambah data',
                'program_kerja.edit' => 'Edit data',
                'program_kerja.delete' => 'Hapus data',
            ],
        ],
        'pekerjaan' => [
            'label' => 'Pekerjaan',
            'permissions' => [
                'pekerjaan.view' => 'Tampilkan menu & tabel',
                'pekerjaan.show' => 'Lihat detail',
                'pekerjaan.create' => 'Tambah data',
                'pekerjaan.edit' => 'Edit data',
                'pekerjaan.delete' => 'Hapus data',
                'pekerjaan.progress' => 'Update progress/checklist',
            ],
        ],
        'rab' => [
            'label' => 'RAB',
            'permissions' => [
                'rab.view' => 'Lihat RAB/detail item',
                'rab.create' => 'Tambah RAB/item',
                'rab.edit' => 'Edit RAB/item',
                'rab.delete' => 'Hapus RAB/item',
            ],
        ],
        'reports' => [
            'label' => 'Laporan',
            'permissions' => [
                'reports.view' => 'Lihat laporan dan audit',
            ],
        ],
        'arsip' => [
            'label' => 'Arsip',
            'permissions' => [
                'arsip.view' => 'Akses menu & data arsip',
            ],
        ],
        'master_data' => [
            'label' => 'Master Data',
            'permissions' => [
                'master_data.view' => 'Tampilkan menu & tabel',
                'master_data.create' => 'Tambah data',
                'master_data.edit' => 'Edit data',
                'master_data.delete' => 'Hapus data',
            ],
        ],
        'users' => [
            'label' => 'User Management',
            'permissions' => [
                'users.view' => 'Tampilkan menu & tabel',
                'users.show' => 'Lihat detail',
                'users.create' => 'Tambah user',
                'users.edit' => 'Edit user',
                'users.delete' => 'Hapus user',
            ],
        ],
        'notifications' => [
            'label' => 'Notifikasi',
            'permissions' => [
                'notifications.view' => 'Lihat notifikasi',
            ],
        ],
    ],
    'keys' => [
        'dashboard.view',
        'program_kerja.view','program_kerja.show','program_kerja.create','program_kerja.edit','program_kerja.delete',
        'pekerjaan.view','pekerjaan.show','pekerjaan.create','pekerjaan.edit','pekerjaan.delete','pekerjaan.progress',
        'rab.view','rab.create','rab.edit','rab.delete',
        'reports.view',
        'arsip.view',
        'master_data.view','master_data.create','master_data.edit','master_data.delete',
        'users.view','users.show','users.create','users.edit','users.delete',
        'notifications.view',
    ],
];
