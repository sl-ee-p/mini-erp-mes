<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('view-audit-log');

        $logs = AuditLog::query()
            ->with('user')
            ->when($request->string('type')->toString(), fn ($query, string $type) => $query->where('auditable_type', $type))
            ->when($request->integer('user_id'), fn ($query, int $userId) => $query->where('user_id', $userId))
            ->when($request->string('event')->toString(), fn ($query, string $event) => $query->where('event', $event))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $types = array_keys(Relation::morphMap());

        return view('audit-logs.index', [
            'logs' => $logs,
            'types' => array_combine($types, array_map(fn (string $type) => str($type)->headline()->toString(), $types)),
            'users' => User::query()->orderBy('name')->pluck('name', 'id')->all(),
            'events' => ['created' => 'Created', 'updated' => 'Updated', 'deleted' => 'Deleted'],
            'recordRoutes' => [
                'user' => 'users.edit',
                'customer' => 'customers.show',
                'material' => 'materials.show',
                'product' => 'products.show',
                'sales_order' => 'sales-orders.show',
                'production_order' => 'production-orders.show',
            ],
        ]);
    }
}
