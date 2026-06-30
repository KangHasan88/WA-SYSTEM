<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use Illuminate\Http\Request;

class ApprovalAuditController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'status' => $request->query('status', 'all'),
            'source' => trim((string) $request->query('source', '')),
            'risk' => $request->query('risk', 'all'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        $approvals = ApprovalRequest::query()
            ->when($filters['status'] !== 'all', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['risk'] !== 'all', fn ($query) => $query->where('risk', $filters['risk']))
            ->when($filters['source'] !== '', fn ($query) => $query->where('source', 'like', '%' . $filters['source'] . '%'))
            ->when($filters['date_from'], fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when($filters['date_to'], fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $summary = [
            'total' => ApprovalRequest::count(),
            'pending' => ApprovalRequest::where('status', 'pending')->count(),
            'approved' => ApprovalRequest::where('status', 'approved')->count(),
            'rejected' => ApprovalRequest::where('status', 'rejected')->count(),
            'expired' => ApprovalRequest::where('status', 'expired')->count(),
        ];

        return view('approval-audits.index', compact('approvals', 'filters', 'summary'));
    }
}
