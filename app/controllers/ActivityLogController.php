<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
    private const ACTIONS = ['login', 'logout', 'create', 'update', 'delete'];

    public function index(): void
    {
        $filters = [
            'q'      => trim($_GET['q'] ?? ''),
            'action' => in_array($_GET['action'] ?? '', self::ACTIONS, true) ? $_GET['action'] : '',
            'date'   => trim($_GET['date'] ?? ''),
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $this->view('activity/index', [
            'title'   => 'Log Aktivitas',
            'user'    => Auth::user(),
            'data'    => (new ActivityLog())->paginate($page, 15, $filters),
            'filters' => $filters,
            'actions' => self::ACTIONS,
        ]);
    }
}